import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

import release


class VersionTests(unittest.TestCase):
    def test_commit_types_and_breaking_markers(self):
        cases = {
            "fix: handle errors": 1,
            "perf(client): reduce allocations": 1,
            "revert: restore behavior": 1,
            "feat(claims): add downloads": 2,
            "fix!: throw API errors": 3,
            "feat(client)!: change the response type": 3,
            "chore: update dependencies\n\nBREAKING CHANGE: PHP 8.3 is needed": 3,
            "fix: change output\n\nBREAKING-CHANGE: output is now an array": 3,
            "docs: add examples": 0,
            "ci: add release workflow": 0,
            "chore(deps): update tools": 0,
        }
        for message, level in cases.items():
            with self.subTest(message=message):
                self.assertEqual(release.parse_commit("a" * 40, message)["level"], level)
        self.assertIsNone(release.parse_commit("a" * 40, "Update the README"))

    def test_semver_resets_lower_components(self):
        self.assertEqual(release.next_version((2, 9, 4), 1), (2, 9, 5))
        self.assertEqual(release.next_version((2, 9, 4), 2), (2, 10, 0))
        self.assertEqual(release.next_version((2, 9, 4), 3), (3, 0, 0))
        self.assertEqual(release.next_version((0, 9, 4), 3), (1, 0, 0))


class RepositoryTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="claimmd-release-test-")
        self.addCleanup(self.temp.cleanup)
        self.addCleanup(os.chdir, Path.cwd())
        root = Path(self.temp.name)
        self.repo = root / "repo"
        self.remote = root / "remote.git"
        self.repo.mkdir()
        scripts = self.repo / ".github" / "scripts"
        scripts.mkdir(parents=True)
        shutil.copy2(Path(release.__file__), scripts / "release.py")
        os.chdir(self.repo)
        self.real_run = release.run
        release.git("init", "--bare", "--initial-branch=main", str(self.remote))
        release.git("init", "--initial-branch=main")
        release.git("config", "user.name", "Release Test")
        release.git("config", "user.email", "release@example.com")
        Path("CHANGELOG.md").write_text("# Changelog\n\n## 2.0.0\n\nPrevious release notes.\n")
        release.git("add", ".")
        release.git("commit", "-m", "Initial release")
        release.git("tag", "v2.0.0")
        release.git("remote", "add", "origin", str(self.remote))
        release.git("push", "-u", "origin", "main", "--tags")
        self.gh_calls = []
        self.released = set()
        self.fail_github = False
        self.race = False
        mock = patch.object(release, "run", side_effect=self.runner)
        mock.start()
        self.addCleanup(mock.stop)

    def runner(self, *args, **kwargs):
        if args[0] == "gh":
            self.gh_calls.append(args)
            tag = args[3]
            url = f"https://github.com/example/package/releases/tag/{tag}"
            if args[2] == "view":
                return subprocess.CompletedProcess(args, 0 if tag in self.released else 1, url, "")
            if self.fail_github:
                raise RuntimeError("GitHub unavailable")
            self.released.add(tag)
            return subprocess.CompletedProcess(args, 0, url, "")
        if self.race and args[:3] == ("git", "push", "--atomic"):
            other = str(Path(self.temp.name) / "concurrent")
            self.real_run("git", "clone", str(self.remote), other)
            self.real_run("git", "-C", other, "config", "user.name", "Concurrent Test")
            self.real_run("git", "-C", other, "config", "user.email", "concurrent@example.com")
            self.real_run("git", "-C", other, "commit", "--allow-empty", "-m", "fix: concurrent change")
            self.real_run("git", "-C", other, "push", "origin", "main")
            self.race = False
        return self.real_run(*args, **kwargs)

    def commit(self, message):
        release.git("commit", "--allow-empty", "-m", message)
        return release.git("rev-parse", "HEAD")

    def push_source(self, message):
        source = self.commit(message)
        release.git("push", "origin", "HEAD:refs/heads/main")
        return source

    def remote_git(self, *args):
        return release.git("--git-dir", str(self.remote), *args)

    def test_highest_bump_wins_and_notes_keep_history(self):
        self.commit("fix: handle upload errors")
        self.commit("feat(claims): add downloads")
        source = self.commit("fix!: change error responses")
        plan = release.plan_release(source, "example/package")
        self.assertEqual(plan["tag"], "v3.0.0")
        self.assertEqual(plan["bump"], "major")
        self.assertIn("### Breaking changes", plan["notes"])
        self.assertIn("### Features", plan["notes"])
        self.assertIn("### Fixes", plan["notes"])
        release.update_changelog(plan["notes"])
        changelog = Path("CHANGELOG.md").read_text()
        self.assertEqual(changelog.count("# Changelog\n"), 1)
        self.assertIn("Previous release notes.", changelog)
        self.assertLess(changelog.index("[3.0.0]"), changelog.index("## 2.0.0"))

    def test_maintenance_commits_do_not_release(self):
        source = self.push_source("docs: document releases")
        self.assertIsNone(release.plan_release(source, "example/package"))
        self.assertEqual(release.publish(source, "example/package")["status"], "skipped")
        self.assertEqual(self.gh_calls, [])
        self.assertEqual(release.git("status", "--porcelain"), "")

    def test_versions_sort_numerically_and_ignore_prereleases(self):
        for tag in ["v2.9.0", "v2.10.0", "v3.0.0-beta.1"]:
            release.git("tag", tag)
        plan = release.plan_release(self.commit("fix: correct encoding"), "example/package")
        self.assertEqual(plan["previous_tag"], "v2.10.0")
        self.assertEqual(plan["tag"], "v2.10.1")

    def test_tags_on_other_branches_do_not_set_the_version(self):
        release.git("checkout", "-b", "other")
        self.commit("feat: other branch")
        release.git("tag", "v9.0.0")
        release.git("checkout", "main")
        plan = release.plan_release(self.commit("fix: main change"), "example/package")
        self.assertEqual(plan["tag"], "v2.0.1")

    def test_dry_run_leaves_files_and_refs_unchanged(self):
        source = self.commit("feat: add claim downloads")
        refs = release.git("show-ref")
        result = self.real_run(sys.executable, ".github/scripts/release.py", "--dry-run")
        self.assertEqual(json.loads(result.stdout)["tag"], "v2.1.0")
        self.assertEqual(release.git("show-ref"), refs)
        self.assertEqual(release.git("rev-parse", "HEAD"), source)
        self.assertEqual(release.git("status", "--porcelain"), "")

    def test_publishing_requires_an_explicit_tested_commit(self):
        result = self.real_run(sys.executable, ".github/scripts/release.py", "--publish", check=False)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("--publish requires --expected-head", result.stderr)

    def test_publish_pushes_changelog_and_annotated_tag(self):
        source = self.push_source("feat: add claim downloads")
        result = release.publish(source, "example/package")
        self.assertEqual(result["tag"], "v2.1.0")
        target = self.remote_git("rev-parse", "main")
        self.assertEqual(self.remote_git("rev-parse", "v2.1.0^{commit}"), target)
        self.assertEqual(self.remote_git("rev-parse", f"{target}^"), source)
        self.assertEqual(self.remote_git("cat-file", "-t", "v2.1.0"), "tag")
        self.assertEqual(self.remote_git("diff", "--name-only", source, target), "CHANGELOG.md")
        self.assertIn("add claim downloads", self.remote_git("cat-file", "-p", "v2.1.0"))
        create = self.gh_calls[-1]
        self.assertIn("--verify-tag", create)
        self.assertIn("--notes-from-tag", create)
        self.assertIn("--latest", create)

    def test_retries_finish_publication_without_another_bump(self):
        source = self.push_source("fix: correct multipart uploads")
        self.fail_github = True
        with self.assertRaisesRegex(RuntimeError, "GitHub unavailable"):
            release.publish(source, "example/package")
        target = self.remote_git("rev-parse", "main")
        release.git("checkout", "--detach", source)
        self.fail_github = False
        result = release.publish(source, "example/package")
        self.assertEqual(result["tag"], "v2.0.1")
        self.assertEqual(self.remote_git("rev-parse", "main"), target)
        self.assertEqual(self.remote_git("tag", "--list"), "v2.0.0\nv2.0.1")
        release.publish(source, "example/package")
        self.assertEqual(len([call for call in self.gh_calls if call[2] == "create"]), 2)

    def test_manual_retry_accepts_the_tagged_release_commit(self):
        source = self.push_source("fix: correct encoding")
        release.publish(source, "example/package")
        result = release.publish(release.git("rev-parse", "HEAD"), "example/package")
        self.assertEqual(result["tag"], "v2.0.1")
        self.assertEqual(len([call for call in self.gh_calls if call[2] == "create"]), 1)

    def test_older_release_recovery_does_not_replace_latest(self):
        source = self.push_source("fix: correct encoding")
        self.fail_github = True
        with self.assertRaises(RuntimeError):
            release.publish(source, "example/package")
        newer = self.push_source("feat: add downloads")
        self.fail_github = False
        release.publish(newer, "example/package")
        release.git("checkout", "--detach", source)
        release.publish(source, "example/package")
        self.assertIn("--latest=false", self.gh_calls[-1])

    def test_stale_runs_skip_publishing(self):
        source = self.push_source("feat: add downloads")
        newer = self.push_source("fix: newer change")
        release.git("checkout", "--detach", source)
        self.assertEqual(release.publish(source, "example/package")["status"], "skipped")
        self.assertEqual(self.remote_git("rev-parse", "main"), newer)
        self.assertEqual(self.gh_calls, [])

    def test_atomic_push_rejects_a_concurrent_main_update(self):
        source = self.push_source("feat: add downloads")
        self.race = True
        with self.assertRaises(RuntimeError):
            release.publish(source, "example/package")
        self.assertEqual(self.remote_git("tag", "--list"), "v2.0.0")
        self.assertEqual(self.remote_git("log", "-1", "--format=%s", "main"), "fix: concurrent change")
        self.assertEqual(self.gh_calls, [])

    def test_dirty_working_trees_are_rejected(self):
        source = self.push_source("fix: correct encoding")
        Path("CHANGELOG.md").write_text("Local edits\n")
        with self.assertRaisesRegex(RuntimeError, "clean working tree"):
            release.publish(source, "example/package")
        self.assertEqual(self.remote_git("rev-parse", "main"), source)

    def test_checkout_must_match_the_tested_commit(self):
        source = self.push_source("fix: correct encoding")
        self.commit("feat: untested change")
        with self.assertRaisesRegex(RuntimeError, "tested commit"):
            release.publish(source, "example/package")

    def test_existing_tags_are_not_overwritten(self):
        release.git("checkout", "-b", "other")
        self.commit("fix: another release")
        release.git("tag", "v2.0.1")
        release.git("checkout", "main")
        source = self.push_source("fix: main change")
        with self.assertRaisesRegex(RuntimeError, "already exists"):
            release.publish(source, "example/package")
        self.assertEqual(release.git("status", "--porcelain"), "")
        self.assertEqual(release.git("rev-parse", "HEAD"), source)


if __name__ == "__main__":
    unittest.main()
