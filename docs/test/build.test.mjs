import assert from 'node:assert/strict'
import { readFile, readdir } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import test from 'node:test'

const dist = new URL('../dist/', import.meta.url)

async function readDistFile(path) {
  return readFile(new URL(path, dist), 'utf8')
}

test('built docs match the GitHub Pages contract', async () => {
  assert.equal(existsSync(dist), true, 'run npm run build before npm run test:build')

  const index = await readDistFile('index.html')
  const sitemap = await readDistFile('sitemap-index.xml')
  const pagefindFiles = await readdir(new URL('pagefind/', dist))
  const assetFiles = await readdir(new URL('_astro/', dist))
  const requiredRoutes = [
    'getting-started/installation/index.html',
    'getting-started/first-tour/index.html',
    'getting-started/hints/index.html',
    'guides/authoring-modes/index.html',
    'guides/control-and-persistence/index.html',
    'guides/events/index.html',
    'guides/styling-and-localization/index.html',
    'guides/dynamic-targets/index.html',
    'guides/security/index.html',
    'reference/tour/index.html',
    'reference/step/index.html',
    'reference/hints/index.html',
    'reference/stimulus/index.html',
    'reference/compatibility/index.html',
  ]
  const routePages = await Promise.all(requiredRoutes.map(readDistFile))
  const css = (
    await Promise.all(assetFiles.filter((file) => file.endsWith('.css')).map((file) => readDistFile(`_astro/${file}`)))
  ).join('\n')
  const output = [index, sitemap, ...routePages].join('\n')

  assert.match(index, /UX Driver/)
  assert.match(index, /Symfony UX product tours/)
  assert.match(index, /href="\/ux-driver\/getting-started\/installation\/"/)
  assert.match(index, /Start tour/)
  assert.match(index, /Highlight/)
  assert.match(index, /Show hints/)
  assert.match(index, /<noscript>[\s\S]*interactive demo needs JavaScript/)
  assert.match(output, /aria-label="Documentation navigation"/)
  assert.match(output, /Start here/)
  assert.match(output, /Guides/)
  assert.match(output, /Reference/)
  assert.match(output, /Next: First tour/)
  assert.match(output, /Previous: Security/)
  assert.match(output, /Edit on GitHub/)
  assert.match(output, /data-pagefind-search/)
  assert.match(output, /aria-label="Toggle theme"/)
  assert.match(routePages[0], /Composer install/)
  assert.match(routePages[0], /class="heading-anchor"/)
  assert.match(routePages[0], /href="#composer-install"/)
  assert.match(routePages[0], /aria-label="Link to this section"/)
  assert.match(output, /\/ux-driver\//)
  assert.match(sitemap, /https:\/\/pentiminax\.github\.io\/ux-driver\//)
  assert.ok(pagefindFiles.includes('pagefind.js'))
  assert.match(css, /\.driver-hint/)
  assert.equal(existsSync(new URL('getting-started/introduction/index.html', dist)), false)
  assert.doesNotMatch(output, /__VITE_PRELOAD__/)
  assert.match(output, /location\.origin/)
  assert.doesNotMatch(output, /ux-datatables|ux-sweet-alert/)
})
