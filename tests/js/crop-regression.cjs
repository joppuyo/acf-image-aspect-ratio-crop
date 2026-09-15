// Run with node tests/js/crop-regression.cjs [path/to/assets/src/input.js].
// No WordPress installation or npm dependencies required.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(
  process.argv[2] || path.join(__dirname, '../../assets/src/input.js'), 'utf8',
).replace(/^import .*;\n/gm, '');
const handlers = new Map();
let settings;
const document = { addEventListener() {}, removeEventListener() {} };
function $(selector) {
  return {
    0: {},
    find() { return this; },
    data(key) { return settings[key]; },
    append() { return this; },
    remove() { return this; },
    off(event) { handlers.delete(selector + ':' + event); return this; },
    on(event, handler) { handlers.set(selector + ':' + event, handler); return this; },
  };
}
const acf = { fields: {}, field: { extend: value => value }, add_action() {} };
class Cropper {
  constructor(image, options) { this.options = options; }
}
vm.runInNewContext(source, {
  jQuery: $, acf, Cropper, document, window: {},
  aiarc_translations: {}, require: () => '',
});
const field = Object.create(acf.fields.image_aspect_ratio_crop);
field.o = { uploader: 'wp' };
field.initialize();

for (const cropType of ['pixel_size', 'aspect_ratio']) {
  settings = {
    crop_type: cropType, aspect_ratio_width: 1300, aspect_ratio_height: 585,
    min_width: 1300, min_height: 585,
  };
  field.openModal({ field: {}, attachment: { attributes: { id: 1, url: 'test.jpg' } } });
  const crop = field.cropper.options.crop;
  let calls = 0;
  let detail = { width: 1200, height: 540 };
  const image = { cropper: { setData() {
    assert(++calls <= 2, cropType + ': recursive minimum correction');
    crop.call(image, { detail }); // Cropper emits another synchronous crop event.
  } } };
  crop.call(image, { detail });
  assert.equal(calls, 1, cropType + ': source clamped below minimum');
  calls = 0;
  detail = { width: 1299.9999999999998, height: 584.9999999999999 };
  crop.call(image, { detail });
  assert.equal(calls, 0, cropType + ': fractional dimensions round to valid pixels');
  detail = { width: 1300, height: 585 };
  crop.call(image, { detail });
  assert.equal(calls, 0, cropType + ': valid size needs no correction');
  detail = { width: 1200, height: 540 };
  image.cropper.setData = () => { throw new Error('setData failed'); };
  assert.throws(() => crop.call(image, { detail }), /setData failed/);
  image.cropper.setData = () => { calls++; };
  crop.call(image, { detail });
  assert.equal(calls, 1, cropType + ': correction guard recovers after an exception');
}

// Reopening replaces namespaced handlers rather than stacking callbacks.
const cancel = '.js-acf-image-aspect-ratio-crop-cancel:click.aiarc';
const reset = '.js-acf-image-aspect-ratio-crop-reset:click.aiarc';
const submit = '.js-acf-image-aspect-ratio-crop-crop:click.aiarc';
assert.equal(typeof handlers.get(submit), 'function', 'crop binds directly to its button');
let cancelled = 0, resets = 0;
field.closeModal = () => { cancelled++; };
field.cropper.reset = () => { resets++; };
handlers.get(cancel)();
handlers.get(reset)();
assert.equal(cancelled, 1);
assert.equal(resets, 1);
assert.equal(handlers.size, 3);
console.log('PASS: minimum-size correction and direct modal button handlers');
