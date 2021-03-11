<?php

if (!function_exists('register_block_type_from_metadata')) {
    /**
     * @since WordPress 5.5.0
     * https://github.com/WordPress/WordPress/blob/5.5-branch/wp-includes/blocks.php#L181
     */
    function register_block_type_from_metadata( $file_or_folder, $args = array() ) {
        $filename      = 'block.json';
        $metadata_file = ( substr( $file_or_folder, -strlen( $filename ) ) !== $filename ) ?
            trailingslashit( $file_or_folder ) . $filename :
            $file_or_folder;
        if ( ! file_exists( $metadata_file ) ) {
            return false;
        }

        $metadata = json_decode( file_get_contents( $metadata_file ), true );
        if ( ! is_array( $metadata ) || empty( $metadata['name'] ) ) {
            return false;
        }
        $metadata['file'] = $metadata_file;

        $settings          = array();
        $property_mappings = array(
            'title'           => 'title',
            'category'        => 'category',
            'parent'          => 'parent',
            'icon'            => 'icon',
            'description'     => 'description',
            'keywords'        => 'keywords',
            'attributes'      => 'attributes',
            'providesContext' => 'provides_context',
            'usesContext'     => 'uses_context',
            'supports'        => 'supports',
            'styles'          => 'styles',
            'example'         => 'example',
        );

        foreach ( $property_mappings as $key => $mapped_key ) {
            if ( isset( $metadata[ $key ] ) ) {
                $settings[ $mapped_key ] = $metadata[ $key ];
            }
        }

        if ( ! empty( $metadata['editorScript'] ) ) {
            $settings['editor_script'] = register_block_script_handle(
                $metadata,
                'editorScript'
            );
        }

        if ( ! empty( $metadata['script'] ) ) {
            $settings['script'] = register_block_script_handle(
                $metadata,
                'script'
            );
        }

        if ( ! empty( $metadata['editorStyle'] ) ) {
            $settings['editor_style'] = register_block_style_handle(
                $metadata,
                'editorStyle'
            );
        }

        if ( ! empty( $metadata['style'] ) ) {
            $settings['style'] = register_block_style_handle(
                $metadata,
                'style'
            );
        }

        return register_block_type(
            $metadata['name'],
            array_merge(
                $settings,
                $args
            )
        );
    }
}
