<?php
function createACFBlock(array $blockConfig, array $fieldsSchema, callable $renderer)
{
    $name         = $blockConfig['name'] ?? null;
    $title        = $blockConfig['title'] ?? null;
    $previewImage = $blockConfig['preview_image'] ?? null;

    if (!$name) {
        throw new InvalidArgumentException('Block name is required.');
    }

    /**
     * Handle the block preview.
     */
    // Before registering block, prepare example data
    $example = $blockConfig['example'] ?? [
        'attributes' => [
            'mode' => 'preview',
            'data' => []
        ]
    ];
    if ($previewImage) {
        $example['attributes']['data']['_the_editor_preview_image'] = $previewImage;
    }

    acf_register_block_type([
        ...$blockConfig,
        'example'         => $example,
        'render_callback' => function ($block, $content = '', $is_preview = false, $post_id = 0) use ($renderer) {

            /**
             * Preview image for the block if it is a block example preview.
             */
            if ($is_preview && isset($block['data']['_the_editor_preview_image'])) {
                ?>
            <img src="<?= esc_url($block['data']['_the_editor_preview_image']); ?>" style="width: 100%" />
            <?php
                return;
            }

            /**
             * Render the block using the provided renderer function.
             */
            $fields = get_fields();
            if (!is_array($fields))
                $fields = [];

            $renderer($fields, [
                'block'      => $block,
                'content'    => $content,
                'is_preview' => $is_preview,
                'post_id'    => $post_id
            ]);
        },
    ]);

    acf_add_local_field_group([
        'key'      => "$name\_acf_block_fields",
        'title'    => "$title Fields",
        'fields'   => $fieldsSchema,
        'location' => [
            [
                [
                    'param'    => 'block',
                    'operator' => '==',
                    'value'    => "acf/$name",
                ],
            ],
        ],
    ]);
}