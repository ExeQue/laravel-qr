/**
 * laravel-qr in the browser: instant checks, labels and messages from the manifest that
 * `<x-qr::manifest />` puts in the page, or that `Qr::manifest()` gives. Codes themselves are
 * drawn on the server; these follow the same rules as its scan guards.
 */

const manifests = new Map()

/**
 * The manifest `<x-qr::manifest />` put in the page, read once; null when there is none.
 */
export function manifest (id = 'laravel-qr-manifest') {
  if (!manifests.has(id)) {
    const element = typeof document === 'undefined' ? null : document.getElementById(id)

    manifests.set(id, element === null ? null : JSON.parse(element.textContent))
  }

  return manifests.get(id)
}

/**
 * The manifest's language closest to `locale`, or to the page's `<html lang>`: the same, then the same
 * language without its region (`da-DK` finds `da`), then the manifest's first.
 */
export function localeOf (given, locale = null) {
  const locales = given?.locales ?? []
  const wanted = locale ?? (typeof document === 'undefined' ? '' : document.documentElement.lang) ?? ''
  const language = wanted.split(/[-_]/)[0]

  return locales.find((candidate) => candidate === wanted) ?? locales.find((candidate) => candidate === language) ?? locales[0] ?? null
}

/** The scan guards' limits when no manifest is given: the package's defaults. */
export const defaults = {
  min_contrast: 3.0,
  on_violation: 'throw',
  inverted: 'warn',
  assumed_background: '#ffffff',
}

/**
 * A CSS color as red, green and blue from 0 to 255 and alpha from 0 to 1: hex (`#rgb`, `#rgba`,
 * `#rrggbb`, `#rrggbbaa`), `rgb()`/`rgba()` with commas or spaces, or `transparent`. Null for
 * anything else, which the server reads but this does not.
 */
export function parseColor (css) {
  if (typeof css !== 'string') {
    return null
  }

  const value = css.trim().toLowerCase()

  if (value === 'transparent') {
    return { r: 0, g: 0, b: 0, a: 0 }
  }

  const hex = value.match(/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/)

  if (hex) {
    const digits = hex[1].length <= 4 ? [...hex[1]].map((digit) => digit + digit).join('') : hex[1]
    const [r, g, b, a = 255] = digits.match(/../g).map((pair) => parseInt(pair, 16))

    return { r, g, b, a: a / 255 }
  }

  const rgb = value.match(/^rgba?\(\s*([\d.]+%?)[\s,]+([\d.]+%?)[\s,]+([\d.]+%?)(?:[\s,/]+([\d.]+%?))?\s*\)$/)

  if (rgb) {
    const channel = (part) => part.endsWith('%') ? parseFloat(part) * 255 / 100 : parseFloat(part)
    const alpha = rgb[4] === undefined ? 1 : rgb[4].endsWith('%') ? parseFloat(rgb[4]) / 100 : parseFloat(rgb[4])

    return { r: channel(rgb[1]), g: channel(rgb[2]), b: channel(rgb[3]), a: Math.min(1, Math.max(0, alpha)) }
  }

  return null
}

const linear = (channel) => channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4
const luminance = ([r, g, b]) => 0.2126 * linear(r) + 0.7152 * linear(g) + 0.0722 * linear(b)
const over = (color, alpha, ground) => color.map((channel, index) => channel * alpha + ground[index] * (1 - alpha))
const channels = (color) => [color.r / 255, color.g / 255, color.b / 255]

/** The background as it shows over white, and the color as it shows over that. */
function shown (color, background, opacity) {
  const fg = typeof color === 'string' ? parseColor(color) : color
  const bg = typeof background === 'string' ? parseColor(background) : background

  if (fg === null || bg === null) {
    return null
  }

  const ground = over(channels(bg), bg.a, [1, 1, 1])

  return [over(channels(fg), fg.a * opacity, ground), ground]
}

/**
 * The WCAG contrast of a color against a background, from 1 to 21: a translucent color counts as it
 * shows over the background, a translucent background as it shows over white. Null for colors it cannot read.
 */
export function contrast (color, background, opacity = 1) {
  const pair = shown(color, background, opacity)

  if (pair === null) {
    return null
  }

  const [a, b] = pair.map(luminance)

  return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05)
}

/** Whether a color shows lighter than its background: a light code on a dark ground. */
export function isLighter (color, background, opacity = 1) {
  const pair = shown(color, background, opacity)

  return pair === null ? null : luminance(pair[0]) > luminance(pair[1])
}

/**
 * How a code's colors stand with the scan guards, as the server would judge them: the contrast, whether
 * the code is lighter than its background, and the worst of it - `fine`, `warning` or `refused` - with the
 * guards that spoke and what they say, in the manifest's language closest to `locale`. A transparent code
 * is read against `assumed_background`. Without a manifest, the one in the page; without one there, the
 * package's defaults and no messages.
 */
export function scannability ({ foreground, background = null, transparent = false, locale = null }, given = manifest()) {
  const limits = { ...defaults, ...(given?.scanSafety ?? {}) }
  const ground = transparent || background === null ? limits.assumed_background : background
  const ratio = contrast(foreground, ground)
  const lighter = isLighter(foreground, ground)
  const problems = []

  if (ratio === null) {
    return { ratio: null, lighter: null, severity: 'unknown', problems }
  }

  const speak = (guard, refuses) => problems.push({ guard, severity: refuses ? 'refused' : 'warning', message: message(guard, { manifest: given, locale }) })

  if (limits.min_contrast !== null && ratio < limits.min_contrast) {
    speak('contrast', limits.on_violation === 'throw')
  }

  if (limits.inverted !== null && lighter) {
    speak('inverted', limits.inverted === 'throw')
  }

  const severity = problems.some((problem) => problem.severity === 'refused')
    ? 'refused'
    : problems.length > 0 ? 'warning' : 'fine'

  return { ratio, lighter, severity, problems }
}

/**
 * A part's label in the manifest's language closest to `locale`: `label('modules', 'dots-medium')`; the
 * name itself for one it does not hold. Kinds: modules, outerEyes, innerEyes, eyes, filters.
 */
export function label (kind, name, { manifest: given = manifest(), locale = null } = {}) {
  return given?.labels?.[localeOf(given, locale)]?.[kind]?.[name] ?? name
}

/**
 * What a guard says about a code it would refuse or warn about, in plain words, in the manifest's
 * language closest to `locale`; null for a guard it does not hold.
 */
export function message (guard, { manifest: given = manifest(), locale = null } = {}) {
  return given?.messages?.[localeOf(given, locale)]?.[guard] ?? null
}
