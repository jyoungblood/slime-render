<?php

/**
 * @package    SLIME Render
 * @version    1.3.3
 * @author     Jonathan Youngblood <jy@hxgf.io>
 * @license    https://github.com/hxgf/slime-render/blob/master/LICENSE.md (MIT License)
 * @source     https://github.com/hxgf/slime-render
 */

namespace Slime;

use LightnCandy\LightnCandy;

use eftec\bladeone\BladeOne;
use eftec\bladeonehtml\BladeOneHtml;
class BladeHTML extends BladeOne {
  use BladeOneHtml;
}

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

    $GLOBALS['hbars_helpers']['@'] = function() {
      static $template_cache = [];
      static $loaded_assets = [];
      static $component_paths = [];
      
      $args = func_get_args();
      $options = end($args);
      $root = $options['data']['root'] ?? [];
      
      // Get component name - early return if invalid
      $component_name = $args[0] ?? null;
      if (!$component_name) {
        return 'ERROR: No component name provided';
      }
      
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
      
      // Load template - early return if not found
      if (!isset($template_cache[$paths['template']])) {
        if (!file_exists($paths['template'])) {
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
      
      // Process template
      $template = $template_cache[$paths['template']];
      
      // Handle slot content if present
      if (isset($options['fn']) && is_callable($options['fn'])) {
        $template = str_replace('{{slot}}', $options['fn']($root), $template);
      }
      
      // Process hash values efficiently
      if (!empty($options['hash'])) {
        $replacements = [];
        foreach ($options['hash'] as $key => $value) {
          if (is_array($value) && isset($value['lookupType']) && $value['lookupType'] === 'lookup') {
            $value = ($value['context'] ?? $root)[$value['key'] ?? ''] ?? '';
          }
          $replacements['{{'.$key.'}}'] = $value ?? '';
        }
        
        // Single strtr() call is faster than multiple str_replace()
        $template = strtr($template, $replacements);
      }
      
      // Clean up remaining variables with single regex
      $template = preg_replace('/{{[^}]+}}/', '', $template);
      
      return $assets_html . $template;
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

  // return a rendered Blade template
  public static function blade_template($template, $data = []){
    // $blade = new BladeOne('./views','./cache',BladeOne::MODE_DEBUG); // MODE_DEBUG allows to pinpoint troubles.
    $blade = new BladeHTML('./views','./cache');
    $blade->pipeEnable=true;
    return $blade->run($template, $data);
  }

  // return a rendered Blade template
  public static function blade($req, $res, $args, $status = 200){
    $body = $res->getBody();
    $body->write(
      render::blade_template(
        $args['template'],
        $args['data']
      )
    );
    return $res->withStatus($status);
  }
  
}

?>