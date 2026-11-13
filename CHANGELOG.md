# Changelog

All notable changes to Cybersalt Related Articles are documented here.

## [Unreleased]

## [1.6.1] - 2026-11-12

### 🔒 Security
- **Security hardening** of the styling options.

### 🐛 Fixed
- **Two copies on one page**: placing the module twice on the same page no longer stops the page with a "Cannot redeclare function" error.
- **Exclude categories**: the categories picked in "Exclude categories" are now actually left out (the multi-select value was being read as text). A comma-separated list from older settings still works. In "Shared tags" mode, articles from excluded categories are now left out of the related list too.
- **Ordering**: the "Order by" and "Direction" options now take effect. The module was using the menu item's ordering instead.
- **Featured filter**: "Only Featured" and "Hide Featured" now work. Before, every article was shown whatever the setting.
- **Category name after heading**: the "Display category name after heading" option now works, on category pages and article pages. It was saved under one name and read under another.
- **Category name link**: on an article page, the link now goes to the article's category instead of a category with the article's ID.

### 🚀 New
- **Link the category name**: a new option (under "Display category name after heading") makes the category name a link to the category.
- **Updates**: the module now gets updates through **System → Update → Extensions**, with release notes behind the Changelog button.
- **File check**: the module's settings show whether every installed file matches the release, so an install or update that didn't finish shows up straight away.

## [1.6.0]

- Current release before this changelog was started.
