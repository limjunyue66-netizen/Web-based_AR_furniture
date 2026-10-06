<?php
declare(strict_types=1);

/**
 * Generate minimal axis-aligned box GLB files at real-world meter scale.
 * Run once: php tools/generate_sample_glbs.php
 */

function pack_u32(int $n): string
{
    return pack('V', $n);
}

function pack_f32(float $n): string
{
    return pack('g', $n);
}

function align4(string $bin): string
{
    $pad = (4 - (strlen($bin) % 4)) % 4;
    return $bin . str_repeat("\x00", $pad);
}

/**
 * Create a colored box GLB centered on XZ, sitting on Y=0.
 * Dimensions are width (X), height (Y), depth (Z) in meters.
 */
function write_box_glb(string $path, float $w, float $h, float $d, array $rgb): void
{
    $hx = $w / 2;
    $hz = $d / 2;
    // 8 corners: bottom y=0, top y=h
    $positions = [
        -$hx, 0,  $hz,
         $hx, 0,  $hz,
         $hx, 0, -$hz,
        -$hx, 0, -$hz,
        -$hx, $h,  $hz,
         $hx, $h,  $hz,
         $hx, $h, -$hz,
        -$hx, $h, -$hz,
    ];
    $indices = [
        0,1,2, 0,2,3, // bottom
        4,6,5, 4,7,6, // top
        0,4,5, 0,5,1, // front
        1,5,6, 1,6,2, // right
        2,6,7, 2,7,3, // back
        3,7,4, 3,4,0, // left
    ];

    $posBin = '';
    foreach ($positions as $v) {
        $posBin .= pack_f32((float) $v);
    }
    $idxBin = '';
    foreach ($indices as $i) {
        $idxBin .= pack('v', $i);
    }
    $idxBin = align4($idxBin);

    $r = $rgb[0];
    $g = $rgb[1];
    $b = $rgb[2];

    $json = [
        'asset' => ['version' => '2.0', 'generator' => 'FORMA sample box'],
        'scene' => 0,
        'scenes' => [['nodes' => [0]]],
        'nodes' => [['mesh' => 0]],
        'meshes' => [[
            'primitives' => [[
                'attributes' => ['POSITION' => 0],
                'indices' => 1,
                'material' => 0,
            ]],
        ]],
        'materials' => [[
            'pbrMetallicRoughness' => [
                'baseColorFactor' => [$r, $g, $b, 1.0],
                'metallicFactor' => 0.05,
                'roughnessFactor' => 0.75,
            ],
            'doubleSided' => true,
        ]],
        'accessors' => [
            [
                'bufferView' => 0,
                'componentType' => 5126,
                'count' => 8,
                'type' => 'VEC3',
                'max' => [$hx, $h, $hz],
                'min' => [-$hx, 0.0, -$hz],
            ],
            [
                'bufferView' => 1,
                'componentType' => 5123,
                'count' => 36,
                'type' => 'SCALAR',
            ],
        ],
        'bufferViews' => [
            ['buffer' => 0, 'byteOffset' => 0, 'byteLength' => strlen($posBin), 'target' => 34962],
            ['buffer' => 0, 'byteOffset' => strlen($posBin), 'byteLength' => strlen($idxBin), 'target' => 34963],
        ],
        'buffers' => [
            ['byteLength' => strlen($posBin) + strlen($idxBin)],
        ],
    ];

    $jsonBin = align4(json_encode($json, JSON_UNESCAPED_SLASHES));
    $binChunk = $posBin . $idxBin;

    $jsonChunkLen = strlen($jsonBin);
    $binChunkLen = strlen($binChunk);
    $totalLen = 12 + 8 + $jsonChunkLen + 8 + $binChunkLen;

    $glb = 'glTF';
    $glb .= pack_u32(2);
    $glb .= pack_u32($totalLen);
    $glb .= pack_u32($jsonChunkLen);
    $glb .= pack_u32(0x4E4F534A); // JSON
    $glb .= $jsonBin;
    $glb .= pack_u32($binChunkLen);
    $glb .= pack_u32(0x004E4942); // BIN
    $glb .= $binChunk;

    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $glb);
}

$root = dirname(__DIR__);
$models = [
    ['modern-armchair.glb', 0.669, 0.900, 0.740, [0.45, 0.38, 0.32]],
    ['dining-chair.glb', 0.450, 0.820, 0.520, [0.55, 0.48, 0.40]],
    ['three-seater-sofa.glb', 2.100, 0.850, 0.950, [0.28, 0.34, 0.38]],
    ['coffee-table.glb', 1.100, 0.420, 0.600, [0.42, 0.30, 0.22]],
    ['platform-bed.glb', 1.600, 0.350, 2.000, [0.62, 0.58, 0.52]],
    ['floor-lamp.glb', 0.350, 1.600, 0.350, [0.85, 0.82, 0.75]],
];

foreach ($models as $m) {
    $path = $root . '/models/' . $m[0];
    write_box_glb($path, $m[1], $m[2], $m[3], $m[4]);
    echo "Wrote {$m[0]}\n";
}

echo "Done.\n";
