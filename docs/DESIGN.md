# Design system — UX Driver docs

Scope: the Astro site in `docs/`. The bundle's PHP and TypeScript are product truth, never restyled from here.

## Direction

**Thesis.** A Symfony bundle for product tours has to look like it knows what a tour is: the site borrows the vocabulary of the thing it documents — a spotlight on one element at a time, a step you are on, a path you are through. Everything else recedes into a reading surface.

**Modes.** `/` is Persuade — it has to make the visitor install. Every `/[...slug]` page is Read — the visitor is here to understand, so structure and scanability outrank expression.

**Own world.** Indigo `#5B5BD6` as the guide, amber `#F4D35E` as the spotlight, Symfony crimson `#E23845` where the ecosystem is named, navy `#0B1020` as the night the spotlight cuts through. Familjen Grotesk for headings, Source Sans 3 for prose, JetBrains Mono for code.

## Tokens

`src/styles/tokens.css` is the only file allowed to hold a raw colour. It has two halves, and the split is the rule:

1. **Primitives** — fixed ramps (`--indigo-*`, `--amber-*`, `--crimson-*`, `--teal-*`, `--slate-*`). Never referenced outside this file.
2. **Semantic tokens** — what components consume: `--bg`, `--surface`, `--surface-sunken`, `--border`, `--text`, `--text-muted`, `--text-subtle`, `--accent`, `--focus`, `--code-bg`, one `fg`/`bg`/`border` triple per aside tone.

A theme change is therefore a one-file change. `:root` carries the complete light palette; `[data-theme='dark']` and `@media (prefers-color-scheme: dark) { :root:not([data-theme='light']) }` redefine only semantic tokens, so the site is correct with JavaScript off.

Scales: type `--text-2xs` → `--text-4xl` (fluid `clamp()` from `--text-xl` up), spacing `--space-1` → `--space-24` on a 4px base, `--radius-sm|md|lg|full`, `--shadow-1|2|3` with separate dark values, `--duration-fast|base|slow` with `--ease-out`.

## Layers

`global.css` declares `@layer tokens, base, layout, components, prose, home` and imports one file per layer. Nothing is written outside a layer, so specificity never decides a conflict — order does.

| File | Owns |
|---|---|
| `tokens.css` | primitives, semantic tokens, both themes |
| `base.css` | reset, `body`, links, focus ring, skip link |
| `layout.css` | header, footer, `.doc-shell` grid, page frames |
| `components.css` | nav, ToC, search, drawer, pager, aside, badge, tabs, code |
| `prose.css` | vertical rhythm inside `.doc-article` |
| `home.css` | the landing page only |

## Layout

`.doc-shell` is a three-column grid at ≥1180px (`--sidebar-w` / content / `--toc-w`), two columns from 900 to 1179px with the ToC folded into a collapsed panel above the article, and one column below 900px where the sidebar becomes a `<dialog>` drawer opened from the section bar.

Prose is capped at `--prose-max` (46rem). Headings take more space above than below, and `text-wrap: balance` keeps them from breaking on one word.

## Components

- **DocsNav** — one component, rendered twice (sidebar and drawer). The nav is derived from the content collection sorted by frontmatter `order`; there is no hand-maintained list to drift.
- **Toc** — `IntersectionObserver` scroll-spy over a band under the sticky header. Renders only past two headings. Collapsed by default below 1180px so it never stands between the reader and the page title.
- **SearchDialog** — `<dialog>`, ⌘K, ARIA combobox with `aria-activedescendant`, section badge on every result. Pagefind is imported through a runtime path so Vite cannot preload it into the bundle.
- **DocsDrawer** — `<dialog>` with Escape, outside-click, and focus returned to the trigger. Opening focuses the current page's link.
- **Aside / Badge / Tabs / Steps / Pager / Breadcrumb** — passed to MDX from `[...slug].astro`. Tabs are real tabs: `role="tablist"`, roving tabindex, arrow keys.

## Content rules

- The `h1` and the lede come from frontmatter, rendered by `[...slug].astro`. No `.mdx` file writes its own `h1`.
- One subject, one page. Events live in `reference/stimulus`, Hints actions in `reference/hints`, `once` in `guides/control-and-persistence`, escaping in `guides/security`; every other page links there.
- Authors link root-relative (`/guides/security/`). `rehypeBaseLinks` adds the GitHub Pages base at build time.
- Pipe tables are wrapped by `rehypeWrapTables` in a labelled, focusable scroll region, so a wide table scrolls itself instead of the page.
- Heading anchors are appended empty and get their `#` from CSS — a text node there would leak into the table of contents.

## Non-negotiables

- No utility-class framework. Tailwind was removed; it was imported and never used.
- Nothing outside `tokens.css` holds a colour literal.
- Every interactive element has a visible focus ring (`--focus` with an amber halo).
- Both themes are checked before shipping, and the dark theme is correct with JavaScript disabled.
