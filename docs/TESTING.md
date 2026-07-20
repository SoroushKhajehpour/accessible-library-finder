# Manual testing checklist

Compact checks for the Accessible Library Finder demo. This is a learning checklist, not a formal audit.

## Pages

- [ ] `/` loads with site name and skip link
- [ ] `/resources` lists Library Resources
- [ ] `/library-status` shows status items or the fallback message
- [ ] `/user/login` shows Username and Password labels

## Keyboard and focus

- [ ] Skip link appears when focused and moves focus to main content
- [ ] Tab order reaches navigation, filters, results, and status items
- [ ] Focus rings are clearly visible on links, inputs, selects, and buttons
- [ ] No interactive control relies on mouse-only behaviour

## Structure and forms

- [ ] Heading order is sensible (page title, then section headings)
- [ ] Exposed filter labels remain visible (`Title contains`, `Subject`, `Resource type`)
- [ ] Submit/reset controls have clear names

## Resources filtering

- [ ] No filters: sample resources appear, sorted by title
- [ ] Title contains search narrows results
- [ ] Subject filter narrows results
- [ ] Resource type filter narrows results
- [ ] Combined filters work together
- [ ] Nonsense title search shows the empty-results message

## Layout and zoom

- [ ] Narrow viewport (~320 CSS px): content remains usable; table stacks or reflows
- [ ] 200% browser zoom: text is readable; nothing important is clipped by fixed heights
- [ ] Long titles/descriptions wrap without horizontal-page traps

## Status page and API behaviour

- [ ] Successful response shows **Available** / **Unavailable** as text (not colour alone)
- [ ] Forced API failure shows the user-facing fallback (no raw exception text)
- [ ] Failure is logged (`ddev drush watchdog:show --type=library_status`)
- [ ] Page is cacheable for five minutes (`#cache max-age` 300); confirm stale remote data can lag briefly

## Access

- [ ] Anonymous users can open `/`, `/resources`, and `/library-status`
- [ ] Logged-in users can open the same pages

## Assistive tech / helpers (spot checks)

- [ ] VoiceOver (or similar) announces status text and filter labels usefully
- [ ] axe DevTools: review and understand any findings (do not treat a clean run as WCAG certification)
- [ ] Lighthouse accessibility category: use as a prompt for issues, not a compliance claim

## Automated checks

```bash
ddev exec vendor/bin/phpunit -c phpunit.xml
ddev exec vendor/bin/phpcs \
  --standard=Drupal,DrupalPractice \
  web/modules/custom/library_status
```
