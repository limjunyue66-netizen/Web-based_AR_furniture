/**
 * Auto-fill product dimension fields from a selected (or current) GLB file.
 * glTF: X = width, Y = height, Z = depth (meters).
 */
(function () {
  'use strict';

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function setStatus(el, message, isError) {
    if (!el) return;
    el.textContent = message || '';
    el.classList.toggle('is-error', !!isError);
  }

  function round(n, digits) {
    var p = Math.pow(10, digits);
    return Math.round(n * p) / p;
  }

  function readU32(view, offset) {
    return view.getUint32(offset, true);
  }

  function parseGlb(arrayBuffer) {
    var view = new DataView(arrayBuffer);
    if (arrayBuffer.byteLength < 20) {
      throw new Error('File is too small to be a GLB.');
    }
    var magic = String.fromCharCode(
      view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3)
    );
    if (magic !== 'glTF') {
      throw new Error('Not a binary GLB file (missing glTF header).');
    }
    var version = readU32(view, 4);
    if (version !== 2) {
      throw new Error('Only glTF 2.0 GLB is supported.');
    }
    var jsonChunkLength = readU32(view, 12);
    var jsonChunkType = readU32(view, 16);
    if (jsonChunkType !== 0x4E4F534A) { // JSON
      throw new Error('Invalid GLB JSON chunk.');
    }
    var jsonBytes = new Uint8Array(arrayBuffer, 20, jsonChunkLength);
    var jsonText = new TextDecoder('utf-8').decode(jsonBytes);
    var gltf = JSON.parse(jsonText);

    var binOffset = 20 + jsonChunkLength;
    var binChunk = null;
    if (binOffset + 8 <= arrayBuffer.byteLength) {
      var binLen = readU32(view, binOffset);
      var binType = readU32(view, binOffset + 4);
      if (binType === 0x004E4942) { // BIN
        binChunk = new Uint8Array(arrayBuffer, binOffset + 8, binLen);
      }
    }

    return { gltf: gltf, bin: binChunk };
  }

  function accessorBounds(gltf, accessorIndex, bin) {
    var accessor = gltf.accessors[accessorIndex];
    if (!accessor) return null;

    if (accessor.min && accessor.max && accessor.min.length >= 3 && accessor.max.length >= 3) {
      return {
        min: [accessor.min[0], accessor.min[1], accessor.min[2]],
        max: [accessor.max[0], accessor.max[1], accessor.max[2]]
      };
    }

    // Fallback: read VEC3 float positions from BIN chunk
    if (!bin || accessor.componentType !== 5126 || accessor.type !== 'VEC3') {
      return null;
    }
    var bufferView = gltf.bufferViews[accessor.bufferView];
    if (!bufferView) return null;
    var byteOffset = (bufferView.byteOffset || 0) + (accessor.byteOffset || 0);
    var stride = bufferView.byteStride || 12;
    var count = accessor.count || 0;
    var min = [Infinity, Infinity, Infinity];
    var max = [-Infinity, -Infinity, -Infinity];
    var dataView = new DataView(bin.buffer, bin.byteOffset, bin.byteLength);

    for (var i = 0; i < count; i++) {
      var o = byteOffset + i * stride;
      var x = dataView.getFloat32(o, true);
      var y = dataView.getFloat32(o + 4, true);
      var z = dataView.getFloat32(o + 8, true);
      if (x < min[0]) min[0] = x;
      if (y < min[1]) min[1] = y;
      if (z < min[2]) min[2] = z;
      if (x > max[0]) max[0] = x;
      if (y > max[1]) max[1] = y;
      if (z > max[2]) max[2] = z;
    }
    if (!isFinite(min[0])) return null;
    return { min: min, max: max };
  }

  function measureGlb(arrayBuffer) {
    var parsed = parseGlb(arrayBuffer);
    var gltf = parsed.gltf;
    var bin = parsed.bin;
    var min = [Infinity, Infinity, Infinity];
    var max = [-Infinity, -Infinity, -Infinity];
    var found = false;

    (gltf.meshes || []).forEach(function (mesh) {
      (mesh.primitives || []).forEach(function (prim) {
        if (!prim.attributes || prim.attributes.POSITION === undefined) return;
        var bounds = accessorBounds(gltf, prim.attributes.POSITION, bin);
        if (!bounds) return;
        found = true;
        for (var i = 0; i < 3; i++) {
          if (bounds.min[i] < min[i]) min[i] = bounds.min[i];
          if (bounds.max[i] > max[i]) max[i] = bounds.max[i];
        }
      });
    });

    if (!found) {
      throw new Error('Could not find mesh position data in this GLB.');
    }

    var sizeX = Math.abs(max[0] - min[0]);
    var sizeY = Math.abs(max[1] - min[1]);
    var sizeZ = Math.abs(max[2] - min[2]);

    return {
      x_m: sizeX,
      y_m: sizeY,
      z_m: sizeZ,
      width_cm: sizeX * 100,
      height_cm: sizeY * 100,
      depth_cm: sizeZ * 100
    };
  }

  function fillFields(size) {
    var map = {
      width_cm: round(size.width_cm, 2),
      height_cm: round(size.height_cm, 2),
      depth_cm: round(size.depth_cm, 2),
      glb_x_m: round(size.x_m, 4),
      glb_y_m: round(size.y_m, 4),
      glb_z_m: round(size.z_m, 4)
    };
    Object.keys(map).forEach(function (name) {
      var input = document.querySelector('[name="' + name + '"]');
      if (input) {
        input.value = String(map[name]);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  function readFileAsArrayBuffer(file) {
    return new Promise(function (resolve, reject) {
      var reader = new FileReader();
      reader.onload = function () { resolve(reader.result); };
      reader.onerror = function () { reject(new Error('Could not read file.')); };
      reader.readAsArrayBuffer(file);
    });
  }

  async function applyFromArrayBuffer(buffer, statusEl, label) {
    setStatus(statusEl, 'Reading GLB size…');
    try {
      var size = measureGlb(buffer);
      fillFields(size);
      setStatus(
        statusEl,
        (label || 'Filled from GLB') + ': '
          + size.width_cm.toFixed(1) + ' × '
          + size.height_cm.toFixed(1) + ' × '
          + size.depth_cm.toFixed(1) + ' cm'
      );
    } catch (err) {
      setStatus(statusEl, err.message || 'Failed to read GLB size.', true);
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var fileInput = $('input[name="glb_file"]');
    var statusEl = $('#glb-size-status');
    var btnCurrent = $('#glb-size-from-current');
    if (!fileInput) return;

    fileInput.addEventListener('change', function () {
      var file = fileInput.files && fileInput.files[0];
      if (!file) return;
      var name = (file.name || '').toLowerCase();
      if (!name.endsWith('.glb')) {
        setStatus(statusEl, 'Auto-size works with .glb files. For .gltf, enter dimensions manually.', true);
        return;
      }
      readFileAsArrayBuffer(file)
        .then(function (buf) { return applyFromArrayBuffer(buf, statusEl, 'Auto-filled from selected file'); })
        .catch(function (err) { setStatus(statusEl, err.message, true); });
    });

    if (btnCurrent) {
      btnCurrent.addEventListener('click', function () {
        var url = btnCurrent.getAttribute('data-glb-url');
        if (!url) {
          setStatus(statusEl, 'No current GLB saved yet. Upload a GLB first.', true);
          return;
        }
        setStatus(statusEl, 'Downloading current GLB…');
        fetch(url, { credentials: 'same-origin' })
          .then(function (res) {
            if (!res.ok) throw new Error('Could not download current GLB (' + res.status + ').');
            return res.arrayBuffer();
          })
          .then(function (buf) { return applyFromArrayBuffer(buf, statusEl, 'Auto-filled from current model'); })
          .catch(function (err) { setStatus(statusEl, err.message || 'Failed.', true); });
      });
    }
  });
})();
