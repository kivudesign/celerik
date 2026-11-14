"""Turns CHANGELOG.md's [Unreleased] section into the section for a release.

Usage: changelog-release.py VERSION DATE PREVIOUS_TAG REPOSITORY_URL

- If CHANGELOG.md already has "## [VERSION]", nothing changes (exit code 0, prints "kept").
- Otherwise the entries under "## [Unreleased]" become "## [VERSION] - DATE", an empty
  "## [Unreleased]" goes above them, and the compare links at the bottom are updated
  (prints "written").
- Exit code 1 if [Unreleased] has no entries: a release never goes out without notes.
"""

import re
import sys

version, date, previous, repository = sys.argv[1:5]
path = 'CHANGELOG.md'
text = open(path, encoding='utf-8').read()

if re.search(r'^## \[' + re.escape(version) + r'\]', text, re.M):
    print('kept')
    sys.exit(0)

heading = re.search(r'^## \[Unreleased\][^\n]*\n', text, re.M)
if heading is None:
    print('CHANGELOG.md has no "## [Unreleased]" section.', file=sys.stderr)
    sys.exit(1)

# The section ends at the next version heading, or at the link definitions.
end = re.compile(r'^(## \[|\[[^\]]+\]: )', re.M).search(text, heading.end())
body = text[heading.end():end.start() if end else len(text)]
if not body.strip():
    print('CHANGELOG.md has nothing under "## [Unreleased]": add the changes of this release.',
          file=sys.stderr)
    sys.exit(1)

text = text[:heading.start()] + f'## [Unreleased]\n\n## [{version}] - {date}\n' + text[heading.end():]

since = f'{repository}/compare/{previous}...v{version}' if previous else f'{repository}/releases/tag/v{version}'
link = f'[{version}]: {since}\n'
unreleased = re.compile(r'^\[Unreleased\]: [^\n]*\n', re.M)
if unreleased.search(text):
    text = unreleased.sub(lambda _: f'[Unreleased]: {repository}/compare/v{version}...HEAD\n' + link, text, count=1)
else:
    text = text.rstrip('\n') + f'\n\n[Unreleased]: {repository}/compare/v{version}...HEAD\n' + link

open(path, 'w', encoding='utf-8', newline='').write(text)
print('written')
