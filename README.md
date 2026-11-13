# Cybersalt Related Articles

A free Joomla 5 / 6 site module that shows visitors where to go next.

- On a **category page** it lists the category's subcategories and its articles.
- On an **article page** it lists the other articles in the same category, or articles that share a tag with it.

Element: `mod_currentcat_children_and_articles`. Product page: [cybersalt.com/extensions/related-articles](https://www.cybersalt.com/extensions/related-articles).

---

## Options

| Group | Options |
|---|---|
| Where it shows | Category pages, article pages, and each section on or off (subcategories, category articles, related articles) |
| Headings | Your own heading text per section, with the category name after it (optionally linked) |
| Subcategories | Depth (1, 2, 3 or unlimited), a limit, and categories to exclude |
| Articles | Order by publish date, created, modified, title, hits or ordering; direction; Include / Only / Hide featured; a limit |
| Related mode | Same category, or shared tags (falls back to same category when the article has no tags) |
| Display | Stack, 2-column or 3-column grid; text, heading, link, link-hover and divider colours; a Custom CSS box |

The module respects access levels, publishing state and the site language, like Joomla's own article lists.

---

## Installing

1. Download the latest `mod_currentcat_children_and_articles_v*.zip` from [Releases](https://github.com/cybersalt/cybersalt-related-articles/releases) or the product page.
2. In Joomla admin, go to **System → Install → Extensions** and upload the zip.
3. Go to **Content → Site Modules → New → Cybersalt Related Articles**, pick a position, and assign it to the menu items where it should show.

Updates arrive through **System → Update → Extensions** like any other extension.

---

## Building

```powershell
powershell -ExecutionPolicy Bypass -File build.ps1 -Release
```

Needs 7-Zip and [Joomla-Brain](https://github.com/cybersalt/Joomla-Brain) beside this repo (for the shipped-file integrity list).

---

## Language support

English (en-GB).

---

## License

GNU General Public License v2 or later. See [LICENSE](LICENSE).

Changes are listed in [CHANGELOG.md](CHANGELOG.md).
