<?php

/* @CoreHome/_topScreen.twig */
class __TwigTemplate_7f3180ff98ca9b5ef5d945e4b25a88e8041d18861f026d27a72cd05823c5cb4d extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<div id=\"header\">
    <a href='#main' tabindex=\"0\" class=\"accessibility-skip-to-content\">";
        // line 2
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CoreHome_SkipToContent")), "html", null, true);
        echo "</a>
    ";
        // line 3
        $this->env->loadTemplate("@CoreHome/_logo.twig")->display($context);
        // line 4
        echo "    ";
        $this->env->loadTemplate("@CoreHome/_topBar.twig")->display($context);
        // line 5
        echo "</div>
";
    }

    public function getTemplateName()
    {
        return "@CoreHome/_topScreen.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  31 => 5,  28 => 4,  26 => 3,  22 => 2,  19 => 1,);
    }
}
