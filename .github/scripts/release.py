import argparse
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import re
import subprocess


VERSION = re.compile(r"v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)")
COMMIT = re.compile(r"(?P<type>[a-z]+)(?:\((?P<scope>[^()\r\n]+)\))?(?P<breaking>!)?: (?P<description>.+)", re.IGNORECASE)
BREAKING = re.compile(r"^BREAKING(?: CHANGE|-CHANGE):\s*\S", re.MULTILINE)


def run(*args, check=True, input=None):
    result = subprocess.run(args, input=input, capture_output=True, text=True)
    if check and result.returncode:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip() or f"{args[0]} failed")
    return result


def git(*args, **kwargs):
    return run("git", *args, **kwargs).stdout.strip()


def stable_tags(ref):
    tags = []
    for tag in git("tag", "--merged", ref).splitlines():
        match = VERSION.fullmatch(tag)
        if match:
            tags.append((tuple(map(int, match.groups())), tag))
    return sorted(tags, reverse=True)


def parse_commit(sha, message):
    subject = message.splitlines()[0]
    match = COMMIT.fullmatch(subject)
    breaking = bool(BREAKING.search(message)) or bool(match and match["breaking"])
    if not match and not breaking:
        return None
    kind = match["type"].lower() if match else "other"
    level = 3 if breaking else 2 if kind == "feat" else 1 if kind in {"fix", "perf", "revert"} else 0
    description = match["description"] if match else subject
    if match and match["scope"]:
        description = f'{match["scope"]}: {description}'
    return {"sha": sha, "type": kind, "breaking": breaking, "level": level, "description": description}


def next_version(version, level):
    major, minor, patch = version
    if level == 3:
        return major + 1, 0, 0
    if level == 2:
        return major, minor + 1, 0
    return major, minor, patch + 1


def release_notes(previous, tag, commits, repository, date):
    url = f"https://github.com/{repository}"
    link = f"{url}/compare/{previous}...{tag}" if previous else f"{url}/releases/tag/{tag}"
    lines = [f"## [{tag[1:]}]({link}) ({date})"]
    groups = {}
    titles = {"feat": "Features", "fix": "Fixes", "perf": "Performance", "revert": "Reverts"}
    for commit in commits:
        title = "Breaking changes" if commit["breaking"] else titles.get(commit["type"], "Maintenance")
        description = re.sub(r"([\\`*_[\]<>])", r"\\\1", commit["description"])
        groups.setdefault(title, []).append(f'- {description} ([{commit["sha"][:7]}]({url}/commit/{commit["sha"]}))')
    for title in ["Breaking changes", "Features", "Fixes", "Performance", "Reverts", "Maintenance"]:
        if title in groups:
            lines.extend(["", f"### {title}", "", *groups[title]])
    return "\n".join(lines) + "\n"


def plan_release(source, repository):
    tags = stable_tags(source)
    version, previous = tags[0] if tags else ((0, 0, 0), None)
    revision = f"{previous}..{source}" if previous else source
    records = git("log", "--format=%H%x00%B%x00", revision).split("\0")
    commits = []
    for index in range(0, len(records) - 1, 2):
        commit = parse_commit(records[index].strip(), records[index + 1].strip())
        if commit:
            commits.append(commit)
    level = max((commit["level"] for commit in commits), default=0)
    if not level:
        return None
    version = ".".join(map(str, next_version(version, level)))
    tag = f"v{version}"
    date = datetime.now(timezone.utc).date().isoformat()
    return {
        "version": version,
        "tag": tag,
        "previous_tag": previous,
        "bump": {1: "patch", 2: "minor", 3: "major"}[level],
        "notes": release_notes(previous, tag, commits, repository, date),
    }


def update_changelog(notes):
    path = Path("CHANGELOG.md")
    existing = path.read_text() if path.exists() else ""
    existing = existing.removeprefix("# Changelog\n").lstrip("\n")
    path.write_text("# Changelog\n\n" + notes.rstrip() + "\n\n" + existing)


def release_subject(tag):
    return f"chore(release): {tag} [skip ci]"


def publish_github_release(tag, repository, latest):
    existing = run("gh", "release", "view", tag, "--repo", repository, "--json", "url", "--jq", ".url", check=False)
    if existing.returncode == 0:
        return existing.stdout.strip()
    return run(
        "gh", "release", "create", tag, "--repo", repository, "--verify-tag",
        "--title", tag, "--notes-from-tag", "--latest" if latest else "--latest=false",
    ).stdout.strip()


def publish(source, repository):
    if git("status", "--porcelain"):
        raise RuntimeError("Release requires a clean working tree")
    if git("rev-parse", "HEAD") != source:
        raise RuntimeError("Checkout does not match the tested commit")
    git("fetch", "origin", "main", "--tags")
    remote_head = git("rev-parse", "refs/remotes/origin/main")
    tags = stable_tags(remote_head)
    for _, tag in tags:
        if git("log", "-1", "--format=%s", tag) != release_subject(tag):
            continue
        commit = git("rev-parse", f"{tag}^{{commit}}")
        parent = git("log", "-1", "--format=%P", tag)
        if commit == source or parent == source:
            url = publish_github_release(tag, repository, tag == tags[0][1])
            return {"status": "released", "tag": tag, "url": url}
    if remote_head != source:
        return {"status": "skipped", "reason": "main advanced beyond the tested commit"}
    plan = plan_release(source, repository)
    if plan is None:
        return {"status": "skipped", "reason": "No release commits since the latest stable tag"}
    tag = plan["tag"]
    if run("git", "show-ref", "--verify", "--quiet", f"refs/tags/{tag}", check=False).returncode == 0:
        raise RuntimeError(f"Tag {tag} already exists")
    update_changelog(plan["notes"])
    git("add", "--", "CHANGELOG.md")
    git("commit", "-m", release_subject(tag))
    git("tag", "-a", tag, "-F", "-", input=plan["notes"])
    git("push", "--atomic", "origin", "HEAD:refs/heads/main", f"refs/tags/{tag}:refs/tags/{tag}")
    url = publish_github_release(tag, repository, latest=True)
    return {"status": "released", "tag": tag, "url": url}


def main():
    parser = argparse.ArgumentParser()
    mode = parser.add_mutually_exclusive_group()
    mode.add_argument("--publish", action="store_true")
    mode.add_argument("--dry-run", action="store_true")
    parser.add_argument("--expected-head")
    parser.add_argument("--repository", default=os.environ.get("GITHUB_REPOSITORY", "Nextvisit/claim-md-php"))
    args = parser.parse_args()
    if args.publish and not args.expected_head:
        parser.error("--publish requires --expected-head")
    os.chdir(Path(__file__).resolve().parents[2])
    source = git("rev-parse", "--verify", f"{args.expected_head or 'HEAD'}^{{commit}}")
    result = publish(source, args.repository) if args.publish else plan_release(source, args.repository)
    print(json.dumps(result or {"status": "skipped", "reason": "No release commits since the latest stable tag"}, indent=2))


if __name__ == "__main__":
    main()
