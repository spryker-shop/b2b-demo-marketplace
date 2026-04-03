---
name: Browser testing patterns
description: MCP Chrome DevTools limitations and workarounds for Spryker storefront testing
type: feedback
---

MCP `hover` tool does not trigger CSS `:hover` pseudoclasses. To verify hover-based UI:
- Use `evaluate_script` to add a `.force-hover` class and temporary `<style>` that mirrors the `:hover` rules
- Or use `evaluate_script` to check computed styles after forcing class toggle
- Real browser hover works correctly; it's only the tool limitation

**Why:** The Chrome DevTools Protocol hover command moves the virtual cursor but the CSS engine sometimes doesn't trigger `:hover` on custom elements.

**How to apply:** When testing hover-based dropdowns, don't rely solely on the MCP hover tool. Use JS class toggling to verify visual state.

---

After `npm run yves` build, always force-reload (`ignoreCache: true`) in the browser before testing. The browser may cache old CSS/JS bundles.

**Why:** Encountered stale CSS cache showing old styles (both panels `display: block`) despite the compiled CSS being correct.

**How to apply:** After any build, navigate with `ignoreCache: true` or use `navigate_page` with reload before testing.
