import assert from 'node:assert/strict'
import { readFile, readdir } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import test from 'node:test'

const dist = new URL('../dist/', import.meta.url)

async function readDistFile(path) {
  return readFile(new URL(path, dist), 'utf8')
}

/** Every documentation page, in sidebar order. */
const docRoutes = [
  'getting-started/installation/',
  'getting-started/first-tour/',
  'getting-started/hints/',
  'guides/authoring-modes/',
  'guides/highlights/',
  'guides/control-and-persistence/',
  'guides/events/',
  'guides/styling-and-localization/',
  'guides/dynamic-targets/',
  'guides/security/',
  'reference/tour/',
  'reference/step/',
  'reference/hints/',
  'reference/stimulus/',
  'reference/compatibility/',
]

const sectionRoutes = ['getting-started/', 'guides/', 'reference/']

test('every route is built', async () => {
  assert.equal(existsSync(dist), true, 'run npm run build before npm run test:build')

  for (const route of [...docRoutes, ...sectionRoutes]) {
    assert.equal(existsSync(new URL(`${route}index.html`, dist)), true, `missing ${route}`)
  }

  assert.equal(existsSync(new URL('404.html', dist)), true)
  // A page that never existed must not reappear through a stale redirect or nav entry.
  assert.equal(existsSync(new URL('getting-started/introduction/index.html', dist)), false)
})

test('the site is addressed from its GitHub Pages sub-path', async () => {
  const index = await readDistFile('index.html')
  const sitemap = await readDistFile('sitemap-index.xml')
  const pages = await Promise.all(docRoutes.map((route) => readDistFile(`${route}index.html`)))
  const output = [index, ...pages].join('\n')

  assert.match(index, /href="\/ux-driver\/getting-started\/installation\/"/)
  assert.match(sitemap, /https:\/\/pentiminax\.github\.io\/ux-driver\//)

  // MDX authors link root-relative; rehypeBaseLinks adds the base at build time.
  assert.match(pages[0], /href="\/ux-driver\/reference\/compatibility\/"/)
  assert.doesNotMatch(output, /href="\/(getting-started|guides|reference)\//)
})

test('search ships and loads without a Vite preload', async () => {
  const security = await readDistFile('guides/security/index.html')
  const pagefindFiles = await readdir(new URL('pagefind/', dist))

  assert.ok(pagefindFiles.includes('pagefind.js'))
  assert.match(security, /<dialog class="search-dialog"/)
  assert.match(security, /data-search-input/)
  assert.match(security, /data-pagefind-body/)
  // Pagefind is imported through a runtime path, so Vite must not have rewritten it.
  assert.match(security, /location\.origin/)
  assert.doesNotMatch(security, /__VITE_PRELOAD__/)
})

test('every documentation page carries the reading chrome', async () => {
  const pages = await Promise.all(docRoutes.map((route) => readDistFile(`${route}index.html`)))

  for (const [i, page] of pages.entries()) {
    const route = docRoutes[i]

    assert.match(page, /aria-label="Breadcrumb"/, `${route} has no breadcrumb`)
    assert.match(page, /<dialog class="docs-drawer"/, `${route} has no mobile drawer`)
    assert.match(page, /aria-label="Documentation navigation"/, `${route} has no nav`)
    assert.match(page, /aria-label="Previous and next pages"/, `${route} has no pager`)
    assert.match(page, /Edit this page on GitHub/, `${route} has no edit link`)
    assert.match(page, /aria-label="Switch to (dark|light) theme"/, `${route} has no theme toggle`)
    // The frontmatter title is the only h1 — no page repeats it in its body.
    assert.equal(page.match(/<h1[\s>]/g).length, 1, `${route} does not have exactly one h1`)
  }

  const first = pages[0]
  const last = pages.at(-1)

  assert.doesNotMatch(first, /pager-prev/, 'the first page must not offer a previous link')
  assert.doesNotMatch(last, /pager-next/, 'the last page must not offer a next link')
})

test('headings are anchored', async () => {
  const installation = await readDistFile('getting-started/installation/index.html')

  assert.match(installation, /id="install"/)
  assert.match(installation, /<a class="heading-anchor"[^>]*href="#install"/)
  assert.match(installation, /aria-label="Link to this section"/)
})

test('section index pages list their own pages', async () => {
  const guides = await readDistFile('guides/index.html')

  assert.match(guides, /<h1>Guides<\/h1>/)
  assert.match(guides, /href="\/ux-driver\/guides\/highlights\/"/)
  assert.match(guides, /href="\/ux-driver\/guides\/security\/"/)
})

test('the home page states what the bundle is', async () => {
  const index = await readDistFile('index.html')

  assert.match(index, /UX Driver/)
  assert.match(index, /<h1>Product tours you write in Twig — or in PHP\./)
  // Both authoring modes are shown on the home page; neither is the hidden one.
  assert.match(index, /Twig Components<\/figcaption>/)
  assert.match(index, /PHP builder<\/figcaption>/)
  assert.match(index, /tourBuilder-&gt;create|tourBuilder->create/)
  assert.match(index, /Start tour/)
  assert.match(index, /Show hints/)
  assert.match(index, /<noscript>[\s\S]*interactive demo needs JavaScript/)
  // The home page is the only entry point to Guides and Reference for a first-time reader.
  assert.match(index, /href="\/ux-driver\/guides\/highlights\/"/)
})

test('the 404 page is a real page', async () => {
  const notFound = await readDistFile('404.html')

  assert.match(notFound, /<h1>This page moved or never existed/)
  assert.match(notFound, /href="\/ux-driver\/getting-started\/installation\/"/)
})

test('Driver.js styles are bundled and no sibling UX package leaked in', async () => {
  const assetFiles = await readdir(new URL('_astro/', dist))
  const css = (
    await Promise.all(
      assetFiles.filter((file) => file.endsWith('.css')).map((file) => readDistFile(`_astro/${file}`)),
    )
  ).join('\n')

  assert.match(css, /\.driver-hint/)
  assert.doesNotMatch(css, /ux-datatables|ux-sweet-alert/)
})
