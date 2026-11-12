# Changelog

All notable changes to Cybersalt Related Articles are documented here.

## [Unreleased]

### 🔒 Security
- **Styling escaping**: the Custom CSS field and the five colour fields are now escaped on output (`<` is encoded), so a value can no longer close the `<style>` block and inject a script.

### 🐛 Fixed
- **Two copies on one page**: placing the module twice on the same page no longer stops the page with a "Cannot redeclare function" error.
- **Exclude categories**: the categories picked in "Exclude categories" are now actually left out (the multi-select value was being read as text). A comma-separated list from older settings still works.
- **Category name after heading**: now shows on category pages too (it was being cleared straight after it was looked up).
- **Category name link**: on an article page, the link now goes to the article's category instead of a category with the article's ID.

## [1.6.0]

- Current release before this changelog was started.
