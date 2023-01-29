<?php

namespace block_course_rating;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

class renderer
{
    public static function get_star()
    {
        return <<<HTML

<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink" width="32" height="32">
  <path d="M9.5 14.25l-5.584 2.936 1.066-6.218L.465 6.564l6.243-.907L9.5 0l2.792 5.657 6.243.907-4.517 4.404 1.066 6.218" />
</svg>
HTML;

    }

    public static function text_for_course($course)
    {
        $instance = block_course_rating_get_instance_block($course->id);

        if ($instance == null) {
            return '';
        }

        $instance = block_course_rating_get_instance_block($course->id);
        $template = block_course_rating_get_template_block($instance);

        $rating = block_course_rating_get_rating($template, $course->id);

        return self::get_svg($rating->percent, $course->id);
    }

    public static function get_svg($percent, $courseid = 0, $userid = 0, $key = 0)
    {
        $maskId = \sprintf('c%su%sk%s', $courseid, $userid, $key);

        return <<<HTML
<svg viewBox="0 0 80 20" xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink">
  <symbol id="stars-full-star" viewBox="0 0 102 18">
		<path d="M9.5 14.25l-5.584 2.936 1.066-6.218L.465 6.564l6.243-.907L9.5 0l2.792 5.657 6.243.907-4.517 4.404 1.066 6.218" />
	</symbol>

	<symbol id="stars-all-star" viewBox="0 0 104 18">
		<use xlink:href="#stars-full-star" />
		<use xlink:href="#stars-full-star" transform="translate(21)" />
		<use xlink:href="#stars-full-star" transform="translate(42)" />
		<use xlink:href="#stars-full-star" transform="translate(63)" />
		<use xlink:href="#stars-full-star" transform="translate(84)" />
	</symbol>
  
  <mask id="{$maskId}">
    <use xlink:href="#stars-all-star" fill="white" />
    <rect x="0" y="0" width="{$percent}%" height="20" fill="#000" />
  </mask>
  
  <use xlink:href="#stars-all-star" fill="orange" />
  <use xlink:href="#stars-all-star" mask="url(#{$maskId})" fill="#333" />
</svg>
HTML;
    }

    public static function text_for_block($course)
    {
        $instance = block_course_rating_get_instance_block($course->id);
        $template = block_course_rating_get_template_block($instance);

        $rating = block_course_rating_get_rating($template, $course->id);
        $title = get_string('rating', 'block_course_rating', $rating);

        $content = self::get_style();
        $content .= '<div style="text-align: center; max-width: 240px; margin: 0 auto">';
        $content .= self::get_svg($rating->percent, $course->id);
        $content .= "<p>$title</p>";
        $content .= '</div>';

        return $content;
    }

    public static function footer_for_block($user, $course, $returnurl)
    {
        $content = block_course_rating_render_btn_vote($user->id, $course->id, $returnurl);
        $content .= block_course_rating_render_btn_download($course->id, $returnurl);
        $content .= block_course_rating_render_btn_view_votes($course->id, $returnurl);

        return $content;
    }

    protected static function get_style()
    {
        return <<<HTML
<style>
.showblockicons .block_course_rating.block .header .title h2:before {
    content: '★';
    font-size: 22px;
}
</style>
HTML;
    }
}