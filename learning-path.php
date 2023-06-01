<?php
/*
Plugin Name: PBD Learning Path
Plugin URI:  https://www.example.com
Description: Custom Learning Path Plugin
Version:     1.0
Author:      Your Name
Author URI:  https://www.example.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: pbd-lp
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'PBD_Learning_Path' ) ) :
class PBD_Learning_Path {
    protected static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    public function __construct() {
        add_action( 'init', array( $this, 'init' ), 0 );
    }

    public function init() {
        add_shortcode( 'pbd_learning_path', array($this, 'pbd_learning_path_func') );
    }

    public function pbd_learning_path_func( $atts ) {
        if ( !is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
            return 'Learndash must be activated to display this learning path';
        }
        $atts = shortcode_atts( array(
            'course_id' => '',
        ), $atts, 'pbd_learning_path' );

        $course_id = $atts['course_id'];
	$user_id = get_current_user_id();
    // Use the course_id to get lessons and topics using LearnDash functions

    $lessons = learndash_get_course_lessons_list($course_id);
    $output = '';
	$output .= '
    <style>
        .learning_path_container{
            display: block;
            flex-direction: column;
            width: 100%;
            max-width: 760px;
            margin: auto;
            height: auto;
        }
        .learning_path {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 100px;
        }
        .learning_path a {
			width: 70px;
			height: 56px;
			border-radius: 50%;
			margin-bottom: 40px;
			position: relative;
			z-index: 1;
        }
        .learning_path a.locked svg .bottom {
            fill: #A4B1EC;
        }
        .learning_path a.locked svg .top {
            fill: #E1E5F9;
        }
		.learning_path a.next svg .bottom {
            fill: #5058A9;
        }
        .learning_path a.next svg .top {
            fill: #677DDF;
        }
		.learning_path a.finish svg .top {
            fill: #E1E5F9;
        }
		.learning_path a.finish svg .bottom {
            fill: #A4B1EC;
        }
		.learning_path a.finish svg .trophy {
            fill: #A4B1EC;
        }
		.learning_path a.finish.completed svg .trophy {
            fill: #F7D219;
        }
		.learning_path a.finish.completed svg .trophy.dark {
            fill: #F3A000;
        }

        .path_mini_step .top {
            fill: #E1E5F9;
        }
        .path_mini_step .bottom {
            fill: #A4B1EC;
        }
        .learning_path a.completed svg .bottom, .learning_path .path_mini_step.completed svg .bottom {
            fill: #52A87F;
        }
        .learning_path a.completed svg .top, .learning_path .path_mini_step.completed svg .top{
            fill: #67D29F;
        }
		.learning_path_banner {
			display: flex;
			background: linear-gradient(268.06deg, #BAB5FA 0%, #677DDE 100%);
			border-radius: 16px;	
			padding: 36.5px 30px;
			margin-bottom: 60px;
		}
		.learning_path_banner .title {
			font-size:24px;
			color: #fff;
			margin-bottom: 10px;
		}
		.learning_path_banner .description {
			font-size:15px;
			color: #fff;
		}
        .learning_path .path_mini_step {
            margin-bottom: 15px;
        }
        .learning_path .tooltip {
            position:absolute;
            width: 140px;
            height: 50px;
            top: -55px;
            left: -35px;
            transform-origin: bottom; 
        }
        .learning_path .tooltip .text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
        }
        @keyframes wobble {
            0% { transform: translateX(0%); }
            15% { transform: translateX(-10%); }
            30% { transform: translateX(10%); }
            45% { transform: translateX(-10%); }
            60% { transform: translateX(10%); }
            75% { transform: translateX(-10%); }
            90% { transform: translateX(10%); }
            100% { transform: translateX(0%); }
        }
        @keyframes seesaw {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            50% { transform: rotate(0deg); }
            75% { transform: rotate(5deg); }
            100% { transform: rotate(0deg); }
          }
    </style>';
	$output .= '<div class="learning_path_container">';
	$prev_completed = false;

  // Reset the array pointer to the beginning
    foreach ( $lessons as $key=>$lesson ) {
        $marginValuePrevious = 0;
        $currentTopic = 1;
        $topics = learndash_topic_dots( $lesson['post']->ID, false, 'array', $user_id);
        $quizzes = learndash_get_lesson_quiz_list($lesson['post']->ID, $user_id);
        $topics = array_merge($topics, $quizzes);

        if ($key === count($lessons)) {
            $quizzes = learndash_get_course_quiz_list( $course_id, $user_id);
            
            $topics = array_merge($topics, $quizzes);
           
        } else {
            // Not the last item
            
        }

		$topic_count = count($topics) + 1; // Need to add one as we need to include the finish step which isn't an LD topic
        $middleIndex = floor( $topic_count / 2 );
        $isEven = $topic_count % 2 == 0;
        $output .= '
			<div class="learning_path_banner">
				<div>
					<div class="title">'.$lesson['post']->post_title.'</div>
					<div class="description">'.strip_tags($lesson['post']->post_content).'</div>
				</div>
				<div>
				</div>
			</div>
		
		';
        
        $output .= '<div class="learning_path">';

        foreach ($topics as $index=>$topic) {
            // if ( $index == 0 || $index == $topic_count - 1 ) {
            //     continue;
            // }
            $completed = false;
            $marginValue;
            $marginIconValue;
            $marginIconStyle;
            $multiplier = 1.25;
            $multiplierExtra = 30;
            if ($isEven && ($index == $middleIndex || $index == $middleIndex - 1)) {
                $marginValue = $middleIndex * 50;
            } elseif ($index <= $middleIndex) {
                $marginValue = $index * 50;
            } else {
                $marginValue = ($topic_count - $index - 1) * 50;
            }
            if ($currentTopic > $middleIndex){
                $multiplier = 1;
                $multiplierExtra = -30;
            }
            if ($currentTopic == $middleIndex && $isEven){
                $multiplier = 1;
                $multiplierExtra = 0;
            }
            if ($key % 2 == 0) {
                $marginStyle = "margin-right: ${marginValue}px;";
                $marginIconValue = $marginValue * $multiplier + $multiplierExtra;
                $marginIconStyle = "margin-right: ".$marginIconValue."px;";
            } else {
                $marginValue = $marginValue * 1.2;
                $marginIconValue = $marginValue * $multiplier + $multiplierExtra;
                $marginStyle = "margin-left: ${marginValue}px;";
                $marginIconStyle = "margin-left: ".$marginIconValue."px;";
            }
            $marginValuePrevious = $marginValue;

            if (!isset($topic->ID)){
                //This is a quiz
                $topic_id = $topic['post']->ID;
                
                if ($topic['status'] == "completed"){
                    $completed = true;
                }
            } else {
                // Use $topic to get the topic status (completed or not)
                // Get the topic status (completed or not)
                $topic_id = $topic->ID;
                $completed = $topic->completed;
            }
            

			// Determine the status
			if ($completed) {
				$status = "completed";
				$prev_completed = true;
			} else {
				$status = $prev_completed ? "next" : "locked";
				$prev_completed = false;
			}
	
            // Build SVG and links based on topic status
            $svg = "";
            if($status == "completed"){
                $svg = '<svg width="70" height="66" viewBox="0 0 70 66" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M35 66C54.33 66 70 52.6044 70 36.08V29H0V36.08C0 52.6044 15.67 66 35 66Z" class="bottom" />
                <ellipse cx="35" cy="28" rx="35" ry="28" class="top"/>
                <path d="M30.9171 39.8691C30.136 40.6502 28.8697 40.6502 28.0887 39.8691L20.4139 32.1944C19.6329 31.4133 19.6329 30.147 20.4139 29.3659L22.3699 27.41C23.1517 26.6281 24.4197 26.6291 25.2004 27.4121L28.0879 30.3082C28.8689 31.0915 30.1374 31.0921 30.9192 30.3096L44.799 16.4157C45.58 15.6339 46.8468 15.6336 47.6282 16.4149L49.5855 18.3723C50.3665 19.1533 50.3665 20.4197 49.5855 21.2007L30.9171 39.8691Z" fill="white"/>
                </svg>';
            } else if($status == "next"){
                $svg = '
                <div class="tooltip">
                    <div class="text">You Are Here</div>
                    <svg width="140" height="56" viewBox="0 0 140 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="138.75" y="46.75" width="137.5" height="45.5" rx="12.75" transform="rotate(-180 138.75 46.75)" fill="white"/>
                    <rect x="138.75" y="46.75" width="137.5" height="45.5" rx="12.75" transform="rotate(-180 138.75 46.75)" stroke="#E1E5F9" stroke-width="1.5"/>
                    <g clip-path="url(#clip0_312_17269)">
                    <path d="M75 45.25L76.6013 45.25L75.5762 46.4801L70.5762 52.4801L70 53.1715L69.4238 52.4801L64.4238 46.4801L63.3987 45.25L65 45.25L75 45.25Z" fill="white" stroke="#E1E5F9" stroke-width="1.5" stroke-linejoin="round"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_312_17269">
                    <rect width="136" height="8" fill="white" transform="matrix(-1 -8.74228e-08 -8.74228e-08 1 138 46)"/>
                    </clipPath>
                    </defs>
                    </svg>
                </div>
				<svg width="70" height="66" viewBox="0 0 70 66" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M35 66C54.33 66 70 52.6044 70 36.08V29H0V36.08C0 52.6044 15.67 66 35 66Z"  class="bottom"/>
				<ellipse cx="35" cy="28" rx="35" ry="28"  class="top"/>
				<path d="M31.5322 33.441C31.0829 29.6308 31.5589 26.1484 33.441 22.8117C31.7289 20.24 29.1863 17.984 26.5272 16C23.312 25.8036 24.0502 31.9305 32.6784 38.8709L31.5322 33.441Z" fill="white"/>
				<path d="M23.2488 29.3029C22.7947 27.5156 22.6976 25.9347 22.7705 24.1158L16 22.2605C17.4692 32.4769 20.8908 37.3556 30.5608 39.767C26.5976 36.4158 24.2226 33.1253 23.2488 29.3029Z" fill="white"/>
				<path d="M34.4075 37.4916C36.0175 30.7357 38.8175 26.1872 44.4174 23.516C44.211 21.0633 43.5578 18.4843 42.7442 16.0024C33.9752 22.5422 31.8819 28.0911 34.4075 37.4916Z" fill="white"/>
				<path d="M43.3975 26.4131C38.8758 29.3782 37.0059 34.2569 35.8767 40.4324C47.5137 38.7568 51.9043 34.4342 54 22.9866C51.3992 23.3606 49.1577 23.8681 47.2223 24.5578C45.9133 25.0265 44.5559 25.653 43.3975 26.4131Z" fill="white"/>
				</svg>
				';
            } else{
                $svg = '<svg width="70" height="66" viewBox="0 0 70 66" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M35 66C54.33 66 70 52.6044 70 36.08V29H0V36.08C0 52.6044 15.67 66 35 66Z"  class="bottom"/>
                <ellipse cx="35" cy="28" rx="35" ry="28"  class="top"/>
                <path d="M39.4 26.8H29.8C29.4817 26.8 29.1765 26.6736 28.9515 26.4485C28.7264 26.2235 28.6 25.9183 28.6 25.6V22C28.6 20.4087 29.2321 18.8826 30.3574 17.7574C31.4826 16.6321 33.0087 16 34.6 16C36.1913 16 37.7174 16.6321 38.8426 17.7574C39.9679 18.8826 40.6 20.4087 40.6 22V25.6C40.6 25.9183 40.4736 26.2235 40.2485 26.4485C40.0235 26.6736 39.7183 26.8 39.4 26.8ZM31 24.4H38.2V22C38.2 21.0452 37.8207 20.1295 37.1456 19.4544C36.4705 18.7793 35.5548 18.4 34.6 18.4C33.6452 18.4 32.7295 18.7793 32.0544 19.4544C31.3793 20.1295 31 21.0452 31 22V24.4Z" fill="#A4B1EC"/>
                <path d="M40.6 24.4H28.6C26.6118 24.4 25 26.0118 25 28V36.4C25 38.3882 26.6118 40 28.6 40H40.6C42.5882 40 44.2 38.3882 44.2 36.4V28C44.2 26.0118 42.5882 24.4 40.6 24.4Z" fill="#A4B1EC"/>
                </svg>';
            }
			$url = get_permalink($topic_id );
            $output .= "<a class='$status' style='$marginStyle' href='$url'>
                $svg
            </a>";
            $output .= '<div style="'.$marginIconStyle.'" class="path_mini_step '.$status .'"><svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M8.75 16.5C13.5825 16.5 17.5 13.1511 17.5 9.02V7.25H0V9.02C0 13.1511 3.91751 16.5 8.75 16.5Z" class="bottom"/>
            <ellipse cx="8.75" cy="7" rx="8.75" ry="7" class="top"/>
            </svg></div>
            ';
            $currentTopic++;
        }
		if ($prev_completed){
			$finish_completed = "completed";
		} else {
			$finish_completed = "";
		}
		$output .= '<a class="finish '.$finish_completed.'" >
            <svg width="70" height="66" viewBox="0 0 70 66" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M35 66C54.33 66 70 52.6044 70 36.08V29H0V36.08C0 52.6044 15.67 66 35 66Z" class="bottom"/>
            <ellipse cx="35" cy="28" rx="35" ry="28" class="top"/>
            <path class="trophy" d="M37.5827 40.0002H32.5059L33.6596 32.6769H36.429L37.5827 40.0002Z" />
            <path class="trophy" d="M37.1986 34.3461C38.3883 33.1564 38.3883 31.2276 37.1986 30.0379C36.0089 28.8483 34.0801 28.8483 32.8904 30.0379C31.7007 31.2276 31.7007 33.1564 32.8904 34.3461C34.0801 35.5358 36.0089 35.5358 37.1986 34.3461Z" />
            <path class="trophy dark" d="M44.9512 27.585H25.1357C22.458 27.585 20.2793 25.4063 20.2793 22.7285C20.2793 20.0507 22.458 17.8721 25.1357 17.8721H44.9512C47.629 17.8721 49.8076 20.0507 49.8076 22.7285C49.8076 25.4063 47.629 27.585 44.9512 27.585ZM25.1357 19.5232C23.3685 19.5232 21.9308 20.9609 21.9308 22.7285C21.9308 24.4962 23.3685 25.9335 25.1357 25.9335H44.9512C46.7185 25.9335 48.1562 24.4958 48.1562 22.7285C48.1562 20.9612 46.7185 19.5232 44.9512 19.5232H25.1357Z" fill="#A4B1EC"/>
            <path class="trophy" d="M43.6282 15.077V24.7692C43.6282 29.5105 39.7854 33.3533 35.0441 33.3533C30.3028 33.3533 26.459 29.5105 26.459 24.7692V15.077H43.6282Z" fill="#A4B1EC"/>
            <path class="trophy dark" d="M44.1837 13.6H25.9067C25.091 13.6 24.4297 14.2613 24.4297 15.077C24.4297 15.8927 25.091 16.554 25.9067 16.554H44.1837C44.9994 16.554 45.6607 15.8927 45.6607 15.077C45.6607 14.2613 44.9994 13.6 44.1837 13.6Z" fill="#A4B1EC"/>
            <path class="trophy" d="M27.2211 14.0177H29.1298C29.2635 14.0177 29.3723 14.1265 29.3723 14.2602V15.8942C29.3723 16.0279 29.2635 16.1367 29.1298 16.1367H27.2211C27.0873 16.1367 26.9785 16.0279 26.9785 15.8942V14.2602C26.9785 14.1265 27.0873 14.0177 27.2211 14.0177Z" fill="#A4B1EC"/>
            <path class="trophy" d="M30.4437 14.0177H31.1555C31.2893 14.0177 31.3981 14.1265 31.3981 14.2602V15.8942C31.3981 16.0279 31.2893 16.1367 31.1555 16.1367H30.4437C30.31 16.1367 30.2012 16.0279 30.2012 15.8942V14.2602C30.2012 14.1265 30.31 14.0177 30.4437 14.0177Z" fill="#A4B1EC"/>
            <path class="trophy dark" d="M39.1059 38.7078H30.9827C29.6061 38.7078 28.4902 39.8236 28.4902 41.2002C28.4902 41.8629 29.0276 42.4003 29.6903 42.4003H40.398C41.0606 42.4003 41.598 41.8629 41.598 41.2002C41.598 39.8236 40.4822 38.7078 39.1056 38.7078H39.1059Z" fill="#A4B1EC"/>
            </svg>
            </a>';
        $output .= '</div>';
    }
	$output .= '</div>';
    $output .= "<script>
    const tooltip = document.querySelector('.learning_path .tooltip');
  
    function startAnimation() {
      tooltip.style.animation = 'seesaw 0.5s ease-in-out infinite';
      setTimeout(stopAnimation, 2500); 
      console.log('starting');
    }
  
    function stopAnimation() {
      tooltip.style.animation = 'none';
      setTimeout(startAnimation, 5000); 
      console.log('stopping');
    }
  
    startAnimation(); // Start the animation when the page loads
  </script>";
    return $output;
    }
}

endif;

function pbd_lp() {
    return PBD_Learning_Path::instance();
}

// Initialize the plugin
pbd_lp();
