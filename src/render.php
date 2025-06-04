<?php

/**
 * @package    SLIME Render
 * @version    1.4.1
 * @author     Jonathan Youngblood <jy@hxgf.io>
 * @license    https://github.com/hxgf/slime-render/blob/master/LICENSE.md (MIT License)
 * @source     https://github.com/hxgf/slime-render
 */

namespace Slime;

use LightnCandy\LightnCandy;

// use eftec\bladeone\BladeOne;
// use eftec\bladeonehtml\BladeOneHtml;

// class BladeHTML extends BladeOne {
//   // Conditionally use BladeOneHtml trait if available
//   public function __construct($views, $cache, $mode = 0) {
//     parent::__construct($views, $cache, $mode);
    
//     // Check if BladeOneHtml trait is available
//     if (class_exists('eftec\bladeonehtml\BladeOneHtml')) {
//       $this->useBladeOneHtml();
//     }
//   }
  
//   // Helper method to use the trait
//   private function useBladeOneHtml() {
//     // This method uses the trait at runtime
//     $trait = new \ReflectionClass('eftec\bladeonehtml\BladeOneHtml');
//     $methods = $trait->getMethods(\ReflectionMethod::IS_PUBLIC);
    
//     foreach ($methods as $method) {
//       $methodName = $method->getName();
//       if (!method_exists($this, $methodName)) {
//         $this->$methodName = function(...$args) use ($methodName) {
//           return BladeOneHtml::$methodName(...$args);
//         };
//       }
//     }
//   }
// }

class render {

	// render data as json string
  public static function json($req, $res, $data = [], $status = 200){
    $res->getBody()->write(json_encode($data));
    return $res->withHeader('content-type', 'application/json')->withStatus($status);
  }

  // define custom helpers
  public static function initialize_handlebars_helpers(){

    $GLOBALS['hbars_helpers']['date'] = function ($arg1, $arg2, $arg3 = false) {
      if (isset($arg1) && $arg1 != ''){
        if ($arg1 == "now"){
          return date($arg2);
        }else{
          if ($arg3 == "convert"){
            return date($arg2, strtotime($arg1));
          }else{
            return date($arg2, $arg1);
          }
        }
      }else{
        return false;
      }
    };

    $GLOBALS['hbars_helpers']['is'] = function ($l, $operator, $r, $options) {
      if ($operator == '=='){
        $condition = ($l == $r);
      }
      if ($operator == '==='){
        $condition = ($l === $r);
      }
      if ($operator == 'not' || $operator == '!='){
        $condition = ($l != $r);
      }	
      if ($operator == '<'){
        $condition = ($l < $r);
      }
      if ($operator == '>'){
        $condition = ($l > $r);
      }
      if ($operator == '<='){
        $condition = ($l <= $r);
      }
      if ($operator == '>='){
        $condition = ($l >= $r);
      }
      if ($operator == 'in'){
        if (gettype($r) == 'array'){
          $condition = (in_array($l, $r));
        }else{
          // expects a csv string
          $condition = (in_array($l, str_getcsv($r)));
        }
      }
      if ($operator == 'typeof'){
        $condition = (gettype($l) == gettype($r));
      }
      if ($condition){
        return $options['fn']();
      }else{
        return $options['inverse']();
      }
    }; 

    $GLOBALS['hbars_helpers']['concat'] = function() {
        $args = func_get_args();
        $options = array_pop($args); // Remove the last argument which is the options array
        return implode('', $args);
    };

    $GLOBALS['hbars_helpers']['component'] = function() {
      static $template_cache = [];
      static $compiled_cache = [];
      static $loaded_assets = [];
      static $component_paths = [];
      
      $args = func_get_args();
      $options = end($args);
      $root = $options['data']['root'] ?? [];
      
      // error_log("DEBUG KOMPONENT START ----------------------------------------");
      // error_log("Args received: " . print_r($args, true));
      // error_log("Options: " . print_r($options, true));
      // error_log("Root data: " . print_r($root, true));
      
      // Get component name - early return if invalid
      $component_name = $args[0] ?? null;
      if (!$component_name) {
        return 'ERROR: No component name provided';
      }
      
      // error_log("Component name: " . $component_name);
      
      // Cache component paths
      if (!isset($component_paths[$component_name])) {
        $template_path = isset($GLOBALS['settings']['templates']['path']) ? $GLOBALS['settings']['templates']['path'] : 'templates';
        $template_extension = isset($GLOBALS['settings']['templates']['extension']) ? $GLOBALS['settings']['templates']['extension'] : 'html';
        $component_paths[$component_name] = [
          'template' => "./$template_path/_components/{$component_name}.{$template_extension}",
          'assets' => "./$template_path/_components/{$component_name}.assets.{$template_extension}"
        ];
      }
      
      // Get cached paths
      $paths = $component_paths[$component_name];
      // error_log("Component paths: " . print_r($paths, true));
      
      // Load template - early return if not found
      if (!isset($template_cache[$paths['template']])) {
        if (!file_exists($paths['template'])) {
          // error_log("ERROR: Template file not found at " . $paths['template']);
          return "ERROR: Component not found at {$paths['template']}";
        }
        $template_cache[$paths['template']] = file_get_contents($paths['template']);
      }
      
      // Get assets if needed
      $assets_html = '';
      if (!isset($loaded_assets[$component_name]) && file_exists($paths['assets'])) {
        $assets_html = file_get_contents($paths['assets']);
        $loaded_assets[$component_name] = true;
      }
      
      // Get template
      $template = $template_cache[$paths['template']];
      // error_log("Raw template content: " . $template);
      
      // Prepare data context by merging root data with hash values BEFORE handling slot
      $context = $root;
      if (!empty($options['hash'])) {
        foreach ($options['hash'] as $key => $value) {
          if (is_array($value) && isset($value['lookupType']) && $value['lookupType'] === 'lookup') {
            $context[$key] = ($value['context'] ?? $root)[$value['key'] ?? ''] ?? '';
          } else {
            $context[$key] = $value;
          }
        }
      }
      
      // Handle slot content if present - now using merged context
      if (isset($options['fn']) && is_callable($options['fn'])) {
        $template = str_replace('{{slot}}', $options['fn']($context), $template);
        // error_log("Template after slot processing: " . $template);
      }
      
      // error_log("Final context data: " . print_r($context, true));
      // error_log("Available helpers: " . print_r(array_keys($GLOBALS['hbars_helpers']), true));
      
      try {
        // error_log("Attempting to compile template...");
        
        // Match exactly how render.php does it
        $compiled = \LightnCandy\LightnCandy::compile($template, array(
          "flags" => \LightnCandy\LightnCandy::FLAG_HANDLEBARS,
          "helpers" => $GLOBALS['hbars_helpers']
        ));
        
        if ($compiled === false) {
          // error_log("ERROR: Compilation failed");
          return 'ERROR: Failed to compile template';
        }
        
        // error_log("Compiled template: " . $compiled);
        
        // Then prepare it
        $renderer = \LightnCandy\LightnCandy::prepare($compiled);
        // error_log("Renderer created successfully");
        
        // Finally render it
        $result = $renderer($context);
        // error_log("Render result: " . $result);
        
        // error_log("DEBUG KOMPONENT END ----------------------------------------");
        return $assets_html . $result;
        
      } catch (Exception $e) {
        error_log("ERROR: Exception during compilation/rendering: " . $e->getMessage());
        error_log($e->getTraceAsString());
        return 'ERROR: Template processing failed: ' . $e->getMessage();
      }
    };

  }

  // render a LightnCandy template, compiled with HBS settings
  public static function lightncandy_html($args){
    $template_path = isset($GLOBALS['settings']['templates']['path']) ? $GLOBALS['settings']['templates']['path'] : 'templates';
    $template_extension = isset($GLOBALS['settings']['templates']['extension']) ? $GLOBALS['settings']['templates']['extension'] : 'html';
    $template = file_get_contents( './' . $template_path .'/'. $args['template'] . '.' . $template_extension );
    if (isset($args['layout'])){
      $layout = explode('{{outlet}}', file_get_contents( './' . $template_path .'/'. $args['layout'] . '.' . $template_extension ));
      $template = $layout[0] . $template . $layout[1];
    }
    preg_match_all('/{{> ([^}}]+)/', $template, $partial_handles);
    $partials = [];
    foreach ($partial_handles[1] as $handle){
      $partials[$handle] = file_get_contents( './' . $template_path .'/'. $handle . '.' . $template_extension );        
    }
    render::initialize_handlebars_helpers();
    return LightnCandy::prepare(
      LightnCandy::compile($template, array(
        "flags" => LightnCandy::FLAG_HANDLEBARS,
        "partials" => $partials,
        "helpers" => $GLOBALS['hbars_helpers']
      ))
    );
  }

  // return a rendered LightnCandy/HBS template
  public static function hbs($req, $res, $args, $status = 200){
    $data = [];
    if (isset($GLOBALS['locals'])){
      $data['locals'] = $GLOBALS['locals'];
    }
    if (isset($_GET)){
      $data['GET'] = $_GET;
    }
    if (isset($_POST)){
      $data['POST'] = $_POST;
    }
    if (isset($args['data'])){
      $data = array_merge($data, $args['data']);
    }
    if (isset($args['title'])){
      $data['title'] = $args['title'];
    }
    $body = $res->getBody();
    $body->write(render::lightncandy_html($args)($data));
    return $res->withStatus($status);
  }

  // return a rendered LightnCandy/HBS template as raw html
  public static function handlebars($args){
    $data = [];
    if (isset($GLOBALS['locals'])){
      $data['locals'] = $GLOBALS['locals'];
    }
    if (isset($_GET)){
      $data['GET'] = $_GET;
    }
    if (isset($_POST)){
      $data['POST'] = $_POST;
    }
    if (isset($args['data'])){
      $data = array_merge($data, $args['data']);
    }
    if (isset($args['title'])){
      $data['title'] = $args['title'];
    }
    return render::lightncandy_html($args)($data);
  }

  // return a url redirect
  public static function redirect($req, $res, $location, $status = 302){
    return $res->withHeader('Location', $location)->withStatus($status);
  }

  // return an HTML string or file
  public static function html($req, $res, $html, $status = 200){
    $body = $res->getBody();
    if (substr($html, -5) == '.html' && file_exists('./'.$html)){
      $html = file_get_contents('./'.$html);
    }
    $body->write($html);
    return $res->withStatus($status);
  }

  // return a plain text string
  public static function text($req, $res, $text, $status = 200){
    $body = $res->getBody();
    $body->write($text);
    return $res->withHeader('Content-Type', 'text/plain')->withStatus($status);
  }

  // // return a rendered Blade template
  // public static function blade_template($template, $data = []){
  //   // $blade = new BladeOne('./views','./cache',BladeOne::MODE_DEBUG); // MODE_DEBUG allows to pinpoint troubles.
  //   $blade = new BladeHTML('./views','./cache');
  //   $blade->pipeEnable=true;
  //   return $blade->run($template, $data);
  // }

  // // return a rendered Blade template
  // public static function blade($req, $res, $args, $status = 200){
  //   $body = $res->getBody();
  //   $body->write(
  //     render::blade_template(
  //       $args['template'],
  //       $args['data']
  //     )
  //   );
  //   return $res->withStatus($status);
  // }
  
}

?>