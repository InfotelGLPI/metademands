# Vendored JavaScript libraries

These libraries are committed to the repository rather than pulled by a package manager, so nothing
records where they come from or which version is embedded. This file is that record: it must be kept
up to date whenever one of these directories is refreshed, so that a published advisory can be
matched against what is actually shipped.

| Path | Library | Version | Upstream | Licence |
| --- | --- | --- | --- | --- |
| `signature/js/signature_pad.umd.min.js` | Signature Pad | 4.1.7 | https://github.com/szimek/signature_pad | MIT |
| `fuse.js`, `fuse.min.js` | Fuse.js | 7.0.0 | https://www.fusejs.io/ | Apache-2.0 |
| `multiselect2/dist/` | Multiselect (crlcu) | 2.5.6 | https://github.com/crlcu/multiselect | MIT |
| `cascading-dropdowns/` | jquery-chained-selects | 2.1.1 | https://github.com/smarek/jquery-chained-selects | MIT |
| `treetable/` | TreeTable (iron-viper) — **local fork**, see below | unversioned upstream, snapshot of 2016-11-08 | https://github.com/iron-viper/jqueryTreeTable | MIT |
| `diacritics.js`, `diacritics.min.js` | diacritics removal helper | n/a — plugin code, not a third party library | — | GPL-3.0 (plugin) |
| `md_fuzzysearch.js` | metademands fuzzy search | n/a — plugin code, not a third party library | — | GPL-3.0 (plugin) |

## Header banners

The files in this directory keep the copyright and licence banner of their own upstream project.
The plugin GPL banner must never be applied here: these files are not owned by the plugin, and the
MIT / Apache-2.0 licences they are distributed under require their own notice to be preserved.

`tools/regenerate_headers.php` excludes `public/lib` (along with `vendor`, `node_modules`, `lib`,
`dist` and `var`) for that reason, mirroring the exclusions of the `tools:licence_headers_check`
command of the core. Keep that exclusion in place when the script is refreshed from another plugin,
and check `git diff public/lib` after any header run: on Windows the core command has been observed
to miss the exclusion and rewrite these files anyway.

`treetable/` and `cascading-dropdowns/` carry no upstream banner at all — the files were vendored
without one. Their version in the table above comes from the upstream release they were taken from,
not from the file itself.

## `treetable/` is a fork, not a pristine copy

`treetable.js` and `treetable.min.js` are an adaptation of iron-viper's TreeTable: the upstream
plugin toggles Bootstrap glyphicons, this copy toggles Tabler icons (`ti ti-chevron-down` /
`ti ti-chevron-right`) so the tree matches the GLPI 11 icon set. Do not overwrite it with an
upstream download without re-applying that change. Upstream carries no version number, and its
last release predates the fork, so an advisory can only be matched by reading the code.

## Refreshing a library

1. Download the release from the upstream URL above.
2. Replace the files in place, keeping the same directory layout (the paths are referenced from
   `setup.php` and from the templates), and keep the upstream banner untouched.
3. Update the version in the table above, in the same commit.
