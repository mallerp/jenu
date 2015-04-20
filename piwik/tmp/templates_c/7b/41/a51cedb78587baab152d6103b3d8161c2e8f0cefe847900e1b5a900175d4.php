<?php

/* @CorePluginsAdmin/browsePluginsActions.twig */
class __TwigTemplate_7b41a51cedb78587baab152d6103b3d8161c2e8f0cefe847900e1b5a900175d4 extends Twig_Template
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
        echo "<div class=\"ui-confirm\" id=\"installPluginByUpload\">
    <h2>";
        // line 2
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CorePluginsAdmin_TeaserExtendPiwikByUpload")), "html", null, true);
        echo "</h2>

    <p class=\"description\"> ";
        // line 4
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CorePluginsAdmin_AllowedUploadFormats")), "html", null, true);
        echo " </p>

    <form enctype=\"multipart/form-data\"
          method=\"post\"
          id=\"uploadPluginForm\"
          action=\"";
        // line 9
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('linkTo')->getCallable(), array(array("action" => "uploadPlugin", "nonce" => (isset($context["installNonce"]) ? $context["installNonce"] : $this->getContext($context, "installNonce"))))), "html", null, true);
        echo "\">
        <input type=\"file\" name=\"pluginZip\">
        <br />
        <input class=\"startUpload\" type=\"submit\" value=\"";
        // line 12
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CorePluginsAdmin_UploadZipFile")), "html", null, true);
        echo "\">
    </form>
</div>

<div class=\"sort\">
    <a href=\"";
        // line 17
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('linkTo')->getCallable(), array(array("sort" => "popular", "query" => ""))), "html", null, true);
        echo "\" ";
        if (("popular" == (isset($context["sort"]) ? $context["sort"] : $this->getContext($context, "sort")))) {
            echo "class=\"active\"";
        }
        echo ">";
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CorePluginsAdmin_SortByPopular")), "html", null, true);
        echo "</a>
    |
    <a href=\"";
        // line 19
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('linkTo')->getCallable(), array(array("sort" => "newest", "query" => ""))), "html", null, true);
        echo "\" ";
        if (("newest" == (isset($context["sort"]) ? $context["sort"] : $this->getContext($context, "sort")))) {
            echo "class=\"active\"";
        }
        echo ">";
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CorePluginsAdmin_SortByNewest")), "html", null, true);
        echo "</a>
    |
    <a href=\"";
        // line 21
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('linkTo')->getCallable(), array(array("sort" => "alpha", "query" => ""))), "html", null, true);
        echo "\" ";
        if (("alpha" == (isset($context["sort"]) ? $context["sort"] : $this->getContext($context, "sort")))) {
            echo "class=\"active\"";
        }
        echo ">";
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("CorePluginsAdmin_SortByAlpha")), "html", null, true);
        echo "</a>
    |
    <form action=\"";
        // line 23
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('linkTo')->getCallable(), array(array("sort" => ""))), "html", null, true);
        echo "\" method=\"POST\">
        <input value=\"";
        // line 24
        echo twig_escape_filter($this->env, (isset($context["query"]) ? $context["query"] : $this->getContext($context, "query")), "html", null, true);
        echo "\" placeholder=\"";
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("General_Search")), "html", null, true);
        echo "\" type=\"text\" name=\"query\"/>
        <button type=\"submit\">";
        // line 25
        echo twig_escape_filter($this->env, call_user_func_array($this->env->getFilter('translate')->getCallable(), array("General_Search")), "html", null, true);
        echo "</button>
    </form>
</div>
";
    }

    public function getTemplateName()
    {
        return "@CorePluginsAdmin/browsePluginsActions.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  92 => 25,  86 => 24,  82 => 23,  71 => 21,  60 => 19,  49 => 17,  41 => 12,  35 => 9,  27 => 4,  22 => 2,  19 => 1,);
    }
}
