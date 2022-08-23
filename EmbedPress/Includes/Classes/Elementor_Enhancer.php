<?php

namespace EmbedPress\Includes\Classes;

use Elementor\Group_Control_Image_Size;
use Elementor\Utils;

class Elementor_Enhancer {
	public static function youtube( $embed, $setting ) {
		if ( isset( $setting['embedpress_pro_embeded_source'] ) && 'youtube' === $setting['embedpress_pro_embeded_source'] && isset( $embed->embed ) && preg_match( '/src=\"(.+?)\"/', $embed->embed, $match ) ) {

			$url_full = $match[1];
			$query    = parse_url( $url_full, PHP_URL_QUERY );
			parse_str( $query, $params );
			$params['controls']       = $setting['embedpress_pro_youtube_display_controls'];
			$params['iv_load_policy'] = $setting['embedpress_pro_youtube_display_video_annotations'];
			$params['fs']             = ( $setting['embedpress_pro_youtube_enable_fullscreen_button'] === 'yes' ) ? 1 : 0;
			$params['rel']             = ( $setting['embedpress_pro_youtube_display_related_videos'] === 'yes' ) ? 1 : 0;
			$params['end']            = $setting['embedpress_pro_youtube_end_time'];
			if ( $setting['embedpress_pro_youtube_auto_play'] === 'yes' ) {
				$params['autoplay'] = 1;
			}
			$params['start'] = $setting['embedpress_pro_video_start_time'];

			$params['color'] = $setting['embedpress_pro_youtube_progress_bar_color'];

			if ( is_embedpress_pro_active() ) {
				$params['modestbranding'] = $setting['embedpress_pro_youtube_modest_branding'];
				if ( $setting['embedpress_pro_youtube_force_closed_captions'] === 'yes' ) {
					$params['cc_load_policy'] = 1;
				}
			}


			preg_match( '/(.+)?\?/', $url_full, $url );
			if ( empty( $url) ) {
               return $embed;
			}
			$url = $url[1];

			// Reassemble the url with the new variables.
			$url_modified = $url . '?';
			foreach ( $params as $paramName => $paramValue ) {
				$url_modified .= $paramName . '=' . $paramValue . '&';
			}
			// Replaces the old url with the new one.
			$embed->embed = str_replace( $url_full, rtrim( $url_modified, '&' ), $embed->embed );
			if ( is_embedpress_pro_active() ) {
				$embed = self::apply_cta_markup( $embed, $setting, 'youtube' );
			}

		}

		return $embed;
	}

	public static function apply_cta_markup( $embed, $settings, $provider_name = '' ) {
		if ( empty( $settings["embedpress_pro_{$provider_name}_logo"] ) || empty( $settings["embedpress_pro_{$provider_name}_logo"]['url'] ) ) {
			return $embed;
		}
		$img = Group_Control_Image_Size::get_attachment_image_html( $settings, "embedpress_pro_{$provider_name}_logo" );
		if ( empty( $img ) ) {
			return $embed;
		}

		$cta    = '';
		$url    = '';
		$target = '';
		$x      = ! empty( $settings["embedpress_pro_{$provider_name}_logo_xpos"] ) && ! empty( $settings["embedpress_pro_{$provider_name}_logo_xpos"]['unit'] ) ? $settings["embedpress_pro_{$provider_name}_logo_xpos"]['unit'] . $settings["embedpress_pro_{$provider_name}_logo_xpos"]['size'] : '10%';

		$y        = ! empty( $settings["embedpress_pro_{$provider_name}_logo_ypos"] ) && ! empty( $settings["embedpress_pro_{$provider_name}_logo_ypos"]['unit'] ) ? $settings["embedpress_pro_{$provider_name}_logo_ypos"]['unit'] . $settings["embedpress_pro_{$provider_name}_logo_ypos"]['size'] : '10%';
		$cssClass = isset( $embed->url ) ? '.ose-uid-' . md5( $embed->url ) : ".ose-{$provider_name}";
		ob_start();
		?>
        <style type="text/css">
            <?php echo esc_html($cssClass); ?>
            {
                text-align: left
            ;
                position: relative
            ;
            }
            <?php echo esc_html($cssClass); ?>
            .watermark {
                border: 0;
                position: absolute;
                bottom: <?php echo esc_html($y); ?>;
                right: <?php echo esc_html($x); ?>;
                max-width: 150px;
                max-height: 75px;
                opacity: 0.25;
                z-index: 5;
                -o-transition: opacity 0.5s ease-in-out;
                -moz-transition: opacity 0.5s ease-in-out;
                -webkit-transition: opacity 0.5s ease-in-out;
                transition: opacity 0.5s ease-in-out;
            }

            <?php echo esc_html($cssClass); ?>
            .watermark:hover {
                opacity: 1;
            }
        </style>
		<?php
		$style = ob_get_clean();

		if ( ! class_exists( '\simple_html_dom' ) ) {
			include_once EMBEDPRESS_PATH_CORE . 'simple_html_dom.php';
		}

		if ( ! empty( $settings["embedpress_pro_{$provider_name}_cta"] ) && ! empty( $settings["embedpress_pro_{$provider_name}_cta"]['url'] ) ) {
			$url = $settings["embedpress_pro_{$provider_name}_cta"]['url'];
		}

		if ( $url ) {
			$atts       = self::get_link_attributes( $settings["embedpress_pro_{$provider_name}_cta"] );
			$attributes = '';
			foreach ( $atts as $att => $value ) {
				$attributes .= $att . '="' . esc_attr( $value ) . '" ';
			}
			$cta .= sprintf( '<a %s>', trim( $attributes ) );
		}


		$imgDom = str_get_html( $img );
		$imgDom = $imgDom->find( 'img', 0 );
		$imgDom->setAttribute( 'class', 'watermark' );
		$imgDom->removeAttribute( 'style' );
		$imgDom->setAttribute( 'width', 'auto' );
		$imgDom->setAttribute( 'height', 'auto' );
		ob_start();
		echo $imgDom;
		$cta .= ob_get_clean();
		$imgDom->clear();
		unset( $img, $imgDom );

		if ( $url ) {
			$cta .= '</a>';
		}
		$dom     = str_get_html( $embed->embed );
		$wrapDiv = $dom->find( "div.ose-{$provider_name}", 0 );
		if ( ! empty( $wrapDiv ) && is_object( $wrapDiv ) ) {
			$wrapDiv->innertext .= $cta;
		}

		ob_start();
		echo $wrapDiv;
		$markup = ob_get_clean();
		$dom->clear();
		unset( $dom, $wrapDiv );

		$embed->embed = $style . $markup;

		return $embed;
	}

	public static function get_link_attributes( $url_control ) {
		$attributes = [];

		if ( ! empty( $url_control['url'] ) ) {
			$allowed_protocols = array_merge( wp_allowed_protocols(), [
				'skype',
				'viber',
			] );

			$attributes['href'] = esc_url( $url_control['url'], $allowed_protocols );
		}

		if ( ! empty( $url_control['is_external'] ) ) {
			$attributes['target'] = '_blank';
		}

		if ( ! empty( $url_control['nofollow'] ) ) {
			$attributes['rel'] = 'nofollow';
		}

		if ( ! empty( $url_control['custom_attributes'] ) ) {
			// Custom URL attributes should come as a string of comma-delimited key|value pairs
			$attributes = array_merge( $attributes, Utils::parse_custom_attributes( $url_control['custom_attributes'] ) );
		}

		return $attributes;
	}

	public static function vimeo( $embed, $setting ) {

		if ( ! isset( $embed->provider_name ) || strtoupper( $embed->provider_name ) !== 'VIMEO' || ! isset( $embed->embed ) || $setting['embedpress_pro_embeded_source'] !== 'vimeo' ) {
			return $embed;
		}
		preg_match( '/src=\"(.+?)\"/', $embed->embed, $match );
		$url_full = $match[1];
		$params   = [
			'color'    => str_replace( '#', '', $setting['embedpress_pro_vimeo_color'] ),
			'title'    => $setting['embedpress_pro_vimeo_display_title'] === 'yes' ? 1 : 0,
			'byline'   => $setting['embedpress_pro_vimeo_display_author'] === 'yes' ? 1 : 0,
			'portrait' => $setting['embedpress_pro_vimeo_avatar'] === 'yes' ? 1 : 0,
		];
		if ( $setting['embedpress_pro_vimeo_auto_play'] === 'yes' ) {
			$params['autoplay'] = 1;
		}

		if ( is_embedpress_pro_active() ) {
			if ( $setting['embedpress_pro_vimeo_loop'] === 'yes' ) {
				$params['loop'] = 1;
			}
			if ( $setting['embedpress_pro_vimeo_autopause'] === 'yes' ) {
				$params['autopause'] = 1;
			}

			$params ['dnt'] = $setting['embedpress_pro_vimeo_dnt'] === 'yes' ? 1 : 0;
		}


		$url_modified = $url_full;
		foreach ( $params as $param => $value ) {
			$url_modified = add_query_arg( $param, $value, $url_modified );
		}


		$url_modified .= '#t=' . $setting['embedpress_pro_video_start_time'];
		// Replaces the old url with the new one.
		$embed->embed = str_replace( $url_full, $url_modified, $embed->embed );
		if ( is_embedpress_pro_active() ) {
			return self::apply_cta_markup( $embed, $setting, 'vimeo' );
		}

		return $embed;
	}

	public static function wistia( $embed, $setting ) {
		if ( ! isset( $embed->provider_name ) || strtoupper( $embed->provider_name ) !== 'WISTIA, INC.' || ! isset( $embed->embed ) || $setting['embedpress_pro_embeded_source'] !== 'wistia' ) {
			return $embed;
		}
		preg_match( '/src=\"(.+?)\"/', $embed->embed, $match );

		$url_full = $match[1];

		// Parse the url to retrieve all its info like variables etc.
		$query = parse_url( $embed->url, PHP_URL_QUERY );
		$url   = str_replace( '?' . $query, '', $url_full );

		parse_str( $query, $params );

		// Set the class in the attributes
		$embed->attributes->class = str_replace( '{provider_alias}', 'wistia', $embed->attributes->class );
		$embed->embed             = str_replace( 'ose-wistia, inc.', 'ose-wistia', $embed->embed );

		// Embed Options
		$embedOptions                   = new \stdClass;
		$embedOptions->videoFoam        = true;
		$embedOptions->fullscreenButton = ( $setting['embedpress_pro_wistia_fullscreen_button'] === 'yes' );
		$embedOptions->smallPlayButton  = ( $setting['embedpress_pro_wistia_small_play_button'] === 'yes' );
		$embedOptions->autoPlay         = ( $setting['embedpress_pro_wistia_auto_play'] === 'yes' );
		$embedOptions->playerColor      = $setting['embedpress_pro_wistia_color'];
		$embedOptions->playbar          = ( $setting['embedpress_pro_wistia_playbar'] === 'yes' );
		$embedOptions->time             = $setting['embedpress_pro_video_start_time'];
		if ( is_embedpress_pro_active() ) {
			$embedOptions->volumeControl = ( $setting['embedpress_pro_wistia_volume_control'] === 'yes' );

			$volume = isset($setting['embedpress_pro_wistia_volume']['size']) ? (float) $setting['embedpress_pro_wistia_volume']['size'] : 0;
			if ( $volume > 1 ) {
				$volume = $volume / 100;
			}
			$embedOptions->volume = $volume;
		}


		// Plugins
		$pluginsBaseURL = plugins_url( '../assets/js/wistia/min', dirname( __DIR__ ) . '/embedpress-Wistia.php' );

		$pluginList = [];

		// Resumable

		if ( $setting['embedpress_pro_wistia_resumable'] === 'yes' ) {
			// Add the resumable plugin
			$pluginList['resumable'] = [
				'src'   => $pluginsBaseURL . '/resumable.min.js',
				'async' => false,
			];
		}


		// Add a fix for the autoplay and resumable work better together
		if ( isset( $options->autoPlay ) ) {
			if ( $setting['embedpress_pro_wistia_resumable'] === 'yes' ) {
				$pluginList['fixautoplayresumable'] = [
					'src' => $pluginsBaseURL . '/fixautoplayresumable.min.js',
				];
			}
		}

		// Closed Captions plugin
		if ( is_embedpress_pro_active() ) {
			if ( $setting['embedpress_pro_wistia_captions'] === 'yes' ) {
				$isCaptionsEnabled          = ( $setting['embedpress_pro_wistia_captions'] === 'yes' );
				$isCaptionsEnabledByDefault = ( $setting['embedpress_pro_wistia_captions_enabled_by_default'] === 'yes' );
				if ( $isCaptionsEnabled ) {
					$pluginList['captions-v1'] = [
						'onByDefault' => $isCaptionsEnabledByDefault,
					];
				}
				$embedOptions->captions        = $isCaptionsEnabled;
				$embedOptions->captionsDefault = $isCaptionsEnabledByDefault;
			}


		}

		// Rewind plugin
		if ( $setting['embedpress_pro_wistia_rewind'] === 'yes' ) {

			$embedOptions->rewindTime = (int) $setting['embedpress_pro_wistia_rewind_time'];
			$pluginList['rewind']     = [
				'src' => $pluginsBaseURL . '/rewind.min.js',
			];

		}
		// Focus plugin
		if ( $setting['embedpress_pro_wistia_focus'] === 'yes' ) {
			$isFocusEnabled             = ( $setting['embedpress_pro_wistia_focus'] === 'yes' );
			$pluginList['dimthelights'] = [
				'src'     => $pluginsBaseURL . '/dimthelights.min.js',
				'autoDim' => $isFocusEnabled,
			];

			$embedOptions->focus = $isFocusEnabled;
		}


		$embedOptions->plugin = $pluginList;
		$embedOptions         = json_encode( $embedOptions );

		// Get the video ID
		$videoId      = self::get_wistia_video_from_url( $embed->url );
		$shortVideoId = substr( $videoId, 0, 3 );

		// Responsive?

		$class = [
			'wistia_embed',
			'wistia_async_' . $videoId,
		];

		$attribs = [
			sprintf( 'id="wistia_%s"', $videoId ),
			sprintf( 'class="%s"', join( ' ', $class ) ),
			sprintf( 'style="width:%spx; height:%spx;"', $embed->attributes->{'data-width'}, $embed->attributes->{'data-width'} ),
		];

		$labels = [
			'watch_from_beginning'       => __( 'Watch from the beginning', 'embedpress-pro' ),
			'skip_to_where_you_left_off' => __( 'Skip to where you left off', 'embedpress-pro' ),
			'you_have_watched_it_before' => __( 'It looks like you\'ve watched<br />part of this video before!', 'embedpress-pro' ),
		];
		$labels = json_encode( $labels );

		preg_match( '/ose-uid-([a-z0-9]*)/', $embed->embed, $matches );
		$uid = $matches[1];

		$html         = "<div class=\"embedpress-wrapper ose-wistia ose-uid-{$uid} responsive\">";
		$html         .= '<script src="https://fast.wistia.com/assets/external/E-v1.js" async></script>';
		$html         .= "<script>window.pp_embed_wistia_labels = {$labels};</script>\n";
		$html         .= "<script>window._wq = window._wq || []; _wq.push({\"{$shortVideoId}\": {$embedOptions}});</script>\n";
		$html         .= '<div ' . join( ' ', $attribs ) . "></div>\n";
		$html         .= '</div>';
		$embed->embed = $html;
		if ( is_embedpress_pro_active() ) {
			return self::apply_cta_markup( $embed, $setting, 'wistia' );
		}

		return $embed;
	}

	/**
	 * Get the Video ID from the URL
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function get_wistia_video_from_url( $url ) {
		// https://fast.wistia.com/embed/medias/xf1edjzn92.jsonp
		// https://ostraining-1.wistia.com/medias/xf1edjzn92
		preg_match( '#\/medias\\\?\/([a-z0-9]+)\.?#i', $url, $matches );

		$id = false;
		if ( isset( $matches[1] ) ) {
			$id = $matches[1];
		}

		return $id;
	}

	public static function soundcloud( $embed, $setting ) {

		if ( ! isset( $embed->provider_name ) || strtoupper( $embed->provider_name ) !== 'SOUNDCLOUD' || ! isset( $embed->embed ) || $setting['embedpress_pro_embeded_source'] !== 'soundcloud' ) {
			return $embed;
		}
		preg_match( '/src=\"(.+?)\"/', $embed->embed, $match );
		$url_full = $match[1];
		$params   = [
			'color'          => str_replace( '#', '', $setting['embedpress_pro_soundcloud_color'] ),
			'visual'         => $setting['embedpress_pro_soundcloud_visual'] === 'yes' ? 'true' : 'false',
			'auto_play'      => $setting['embedpress_pro_soundcloud_autoplay'] === 'yes' ? 'true' : 'false',
			'sharing'        => $setting['embedpress_pro_soundcloud_share_button'] === 'yes' ? 'true' : 'false',
			'show_comments'  => $setting['embedpress_pro_soundcloud_comments'] === 'yes' ? 'true' : 'false',
			'show_artwork'   => $setting['embedpress_pro_soundcloud_artwork'] === 'yes' ? 'true' : 'false',
			'show_playcount' => $setting['embedpress_pro_soundcloud_play_count'] === 'yes' ? 'true' : 'false',
			'show_user'      => $setting['embedpress_pro_soundcloud_user_name'] === 'yes' ? 'true' : 'false',
			'buying'         => 'false',
			'download'       => 'false',
		];
		if ( is_embedpress_pro_active() ) {
			$params['buying']   = $setting['embedpress_pro_soundcloud_buy_button'] === 'yes' ? 'true' : 'false';
			$params['download'] = $setting['embedpress_pro_soundcloud_download_button'] === 'yes' ? 'true' : 'false';
		}

		$url_modified = $url_full;
		foreach ( $params as $param => $value ) {
			$url_modified = add_query_arg( $param, $value, $url_modified );
		}

		// Replaces the old url with the new one.
		$embed->embed = str_replace( $url_full, $url_modified, $embed->embed );
		if ( 'false' === $params['visual'] ) {
			$embed->embed = str_replace( 'height="400"', 'height="200 !important"', $embed->embed );
		}

		return $embed;
	}

	public static function dailymotion( $embed, $setting ) {
		if ( ! isset( $embed->provider_name ) || strtoupper( $embed->provider_name ) !== 'DAILYMOTION' || ! isset( $embed->embed ) || $setting['embedpress_pro_embeded_source'] !== 'dailymotion' ) {
			return $embed;
		}
		preg_match( '/src=\"(.+?)\"/', $embed->embed, $match );
		$url_full = $match[1];
		$params   = [
			'ui-highlight'         => str_replace( '#', '', $setting['embedpress_pro_dailymotion_control_color'] ),
			'start'                => isset( $setting['embedpress_pro_video_start_time'] ) ? (int) $setting['embedpress_pro_video_start_time'] : 0,
			'mute'                 => $setting['embedpress_pro_dailymotion_mute'] === 'yes' ? 1 : 0,
			'autoplay'             => $setting['embedpress_pro_dailymotion_autoplay'] === 'yes' ? 1 : 0,
			'controls'             => $setting['embedpress_pro_dailymotion_player_control'] === 'yes' ? 1 : 0,
			'ui-start-screen-info' => $setting['embedpress_pro_dailymotion_video_info'] === 'yes' ? 1 : 0,
			'endscreen-enable'     => 0,
		];

		if ( $setting['embedpress_pro_dailymotion_play_on_mobile'] === 'yes' ) {
			$params['playsinline'] = 1;
		}
		if ( is_embedpress_pro_active() ) {
			$params['ui-logo'] = isset( $setting['embedpress_pro_dailymotion_ui_logo']) && ($setting['embedpress_pro_dailymotion_ui_logo'] === 'yes') ? 1 : 0;
		}
		$url_modified = $url_full;
		foreach ( $params as $param => $value ) {
			$url_modified = add_query_arg( $param, $value, $url_modified );
		}
		$embed->embed = str_replace( $url_full, $url_modified, $embed->embed );
		if ( is_embedpress_pro_active() ) {
			return self::apply_cta_markup( $embed, $setting, 'dailymotion' );
		}
		return $embed;
	}

	public static function twitch( $embed_content, $settings ) {
		if ( ! isset( $embed_content->embed ) || $settings['embedpress_pro_embeded_source'] !== 'twitch' ) {
			return $embed_content;
		}
		$e           = current( $embed_content );

		if ( ! isset( $e['provider_name'] ) || strtoupper( $e['provider_name'] ) !== 'TWITCH' ) {
            return $embed_content;
		}
		$time        = '0h0m0s';
		$type        = isset( $e['type'] ) ? $e['type'] : '';
		$content_id  = isset( $e['content_id'] ) ? $e['content_id'] : '';
		$channel     = 'channel' === $type ? $content_id : '';
		$video       = 'video' === $type ? $content_id : '';
		$full_screen = ( 'yes' === $settings['embedpress_pro_fs'] ) ? 'true' : 'false';
		$autoplay    = ( 'yes' === $settings['embedpress_pro_twitch_autoplay'] ) ? 'true' : 'false';
		$layout      = 'video';
		$width       = (int) $settings['width']['size'];
		$height      = (int) $settings['height']['size'];
		if ( ! empty( $settings['embedpress_pro_video_start_time'] ) ) {
			$ta   = explode( ':', gmdate( "G:i:s", $settings['embedpress_pro_video_start_time'] ) );
			$h    = $ta[0] . 'h';
			$m    = ( $ta[1] * 1 ) . 'm';
			$s    = ( $ta[2] * 1 ) . 's';
			$time = $h . $m . $s;
		}
		$muted = ( 'yes' === $settings['embedpress_pro_twitch_mute'] ) ? 'true' : 'false';
		$theme = ! empty( $settings['embedpress_pro_twitch_theme'] ) ? $settings['embedpress_pro_twitch_theme'] : 'dark';
		if ( is_embedpress_pro_active() ) {

			$layout = ( 'yes' === $settings['embedpress_pro_twitch_chat'] ) ? 'video-with-chat' : 'video';

		}

		$url      = "https://embed.twitch.tv?autoplay={$autoplay}&channel={$channel}&height={$height}&layout={$layout}&migration=true&muted={$muted}&theme={$theme}&time={$time}&video={$video}&width={$width}&allowfullscreen={$full_screen}";

		$pars_url = wp_parse_url( get_site_url() );
		$url      = ! empty( $pars_url['host'] ) ? $url . '&parent=' . $pars_url['host'] : $url;

		preg_match( '/src=\"(.+?)\"/', $embed_content->embed, $match );
		$url_full             = $match[1];
		$embed_content->embed = str_replace( $url_full, $url, $embed_content->embed );
		if ( is_embedpress_pro_active() ) {
			return self::apply_cta_markup( $embed_content, $settings, 'twitch' );
		}

		return $embed_content;
	}

	public static function spotify( $embed, $setting ) {
		if ( ! isset( $embed->provider_name ) || strtolower( $embed->provider_name ) !== 'spotify' || ! isset( $embed->embed ) ) {
			return $embed;
		}
		preg_match( '/src=\"(.+?)\"/', $embed->embed, $match );
		$url_full     = $match[1];
		$modified_url = str_replace( 'playlist-v2', 'playlist', $url_full );
		if ( $setting['embedpress_pro_embeded_source'] == 'spotify' ) {
			// apply elementor related mod
			if ( isset( $setting['spotify_theme'] ) ) {
				if ( strpos( $modified_url, '?' ) !== false ) {
					$modified_url .= '&theme=' . sanitize_text_field( $setting['spotify_theme'] );
				} else {
					$modified_url .= '?theme=' . sanitize_text_field( $setting['spotify_theme'] );
				}
			}
		}

		$embed->embed = str_replace( $url_full, $modified_url, $embed->embed );

		return $embed;
	}
}