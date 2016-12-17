<?php

class MY_Loader extends CI_Loader {
    public function template($template_name, $vars = array(), $return = FALSE)
    {
        $common_vars['current_template'] = $template_name;  
        if (!empty($_SESSION['USER'])) {                        
            $common_vars['user'] = $_SESSION['USER'];
        }
        
        if ($template_name == "control_panel") {
            $common_vars['css_files'] = $vars->css_files;
            $common_vars['js_files'] = $vars->js_files;
        }
        
        if ( !empty($_SESSION['facebook_access_token']) ) {
            $common_vars['projects'] = getProjects();
        }
        
        if($return):
            $content  = $this->view('header', $common_vars, $return);
            $content .= $this->view($template_name, $vars, $return);
            $content .= $this->view('footer', $common_vars, $return);

            return $content;
        else:
            $this->view('header', $common_vars);
            $this->view($template_name, $vars);
            $this->view('footer', $common_vars);
        endif;
    }
}