// Reads one QR code with zxing-wasm, from the application's own node_modules (given as the first
// argument). Request on stdin: {"image": "<base64>", "svg": false}; with "svg": true the image is
// SVG and is rasterized with @resvg/resvg-wasm first. Answer on stdout: {"text": "..."} or
// {"text": null}, or {"error": "..."} when a package is missing.
import { readFileSync } from 'node:fs'
import { createRequire } from 'node:module'
import { delimiter, dirname, join } from 'node:path'
import { pathToFileURL } from 'node:url'

// Packages are looked for in the given node_modules, then wherever npx put them on the PATH.
const places = [process.argv[2], ...(process.env.PATH ?? '').split(delimiter).filter((entry) => entry.endsWith(join('node_modules', '.bin'))).map((entry) => dirname(entry))]
const resolve = (name) => {
  for (const place of places) {
    try {
      return createRequire(join(place, 'noop.js')).resolve(name)
    } catch {}
  }

  throw new Error(`Cannot find ${name}`)
}
const require = { resolve }
// A CommonJS entry point comes back as a default export holding its functions.
const load = async (name) => {
  const module = await import(pathToFileURL(require.resolve(name)).href)

  return module.default && Object.keys(module).length <= 2 ? { ...module.default, ...module } : module
}
const wasm = (name) => {
  const bytes = readFileSync(require.resolve(name))

  return bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength)
}

const request = JSON.parse(readFileSync(0, 'utf8'))
let zxing
let resvg

try {
  zxing = await load('zxing-wasm/reader')
  // Load the WebAssembly from node_modules; by default zxing-wasm would fetch it from a CDN.
  zxing.prepareZXingModule({ overrides: { wasmBinary: wasm('zxing-wasm/reader/zxing_reader.wasm') } })

  if (request.svg) {
    resvg = await load('@resvg/resvg-wasm')
    await resvg.initWasm(wasm('@resvg/resvg-wasm/index_bg.wasm'))
  }
} catch (error) {
  process.stdout.write(JSON.stringify({ error: String(error?.message ?? error) }))
  process.exit(0)
}

try {
  let image = new Uint8Array(Buffer.from(request.image, 'base64'))

  if (request.svg) {
    image = new resvg.Resvg(Buffer.from(image).toString('utf8'), { fitTo: { mode: 'width', value: 800 }, background: 'white' }).render().asPng()
  }

  // An image without a code gives a result marked invalid, with empty text.
  const code = (await zxing.readBarcodes(image, { formats: ['QRCode'] })).find((result) => result.isValid)

  process.stdout.write(JSON.stringify({ text: code?.text ?? null, version: code?.version ? Number(code.version) : null }))
} catch {
  // An image it cannot make out holds no code it can read.
  process.stdout.write(JSON.stringify({ text: null }))
}
