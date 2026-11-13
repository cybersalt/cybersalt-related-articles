<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_currentcat_children_and_articles
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\Component\Content\Site\Helper\RouteHelper;

$app   = Factory::getApplication();
$input = $app->getInput();

$option = $input->getCmd('option');
$view   = $input->getCmd('view');

$childCategories = [];
$currentCategoryTitle = '';
$articles        = [];
$siblingArticles = [];

// Guarded, so a second copy of the module on the same page doesn't redeclare these functions.
if (!function_exists('modCurrentCat_collectChildren')) {
/**
 * Collect children up to a depth. depth=1 means direct children only; 0 means unlimited.
 * $exclude is an array of category IDs to skip.
 */
function modCurrentCat_collectChildren($node, int $depth, array $exclude, array &$out, int $limit = 0) {
    if (!$node) { return; }
    $children = $node->getChildren();
    foreach ($children as $child) {
        if (!empty($exclude) && in_array((int) $child->id, $exclude, true)) {
            continue;
        }
        $out[] = $child;
        if ($limit > 0 && count($out) >= $limit) {
            return;
        }
        if ($depth === 1) {
            continue;
        }
        $nextDepth = ($depth === 0) ? 0 : max(0, $depth - 1);
        if ($nextDepth !== 0) {
            modCurrentCat_collectChildren($child, $nextDepth, $exclude, $out, $limit);
            if ($limit > 0 && count($out) >= $limit) {
                return;
            }
        } else {
            // Unlimited: still traverse
            modCurrentCat_collectChildren($child, 0, $exclude, $out, $limit);
            if ($limit > 0 and count($out) >= $limit) {
                return;
            }
        }
    }
}

function modCurrentCat_resolveCategoryTitle($option, $view, $input, $article = null) {
    $title = '';
    try {
        $categories = \Joomla\CMS\Categories\Categories::getInstance('content');
        if ($option === 'com_content' && $view === 'category') {
            $cid = (int) $input->getInt('id');
            if ($cid) { $node = $categories->get($cid); if ($node) { return (string) $node->title; } }
            $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
            if ($menu && ($menu->component ?? '') === 'com_content' && ($menu->query['view'] ?? '') === 'category') {
                $cid = (int) ($menu->query['id'] ?? 0);
                if ($cid) { $node = $categories->get($cid); if ($node) { return (string) $node->title; } }
            }
        }
        if ($option === 'com_content' && $view === 'article') {
            $catid = 0;
            if ($article && !empty($article->catid)) { $catid = (int) $article->catid; }
            if (!$catid) { $catid = (int) $input->getInt('catid'); }
            if ($catid) { $node = $categories->get($catid); if ($node) { return (string) $node->title; } }
        }
    } catch (\Throwable $e) { /* ignore */ }
    return $title;
}
}


$showCatView   = (int) $params->get('show_on_category_view', 1);
$showArtView   = (int) $params->get('show_on_article_view', 1);
$showSubSec    = (int) $params->get('show_section_subcategories', 1);
$showCatArtSec = (int) $params->get('show_section_category_articles', 1);
$showSibSec    = (int) $params->get('show_section_siblings', 1);

$orderCat   = (string) $params->get('order_by_category', 'publish_up');
$dirCat     = (string) $params->get('order_dir_category', 'DESC');
$orderSib   = (string) $params->get('order_by_siblings', 'publish_up');
$dirSib     = (string) $params->get('order_dir_siblings', 'DESC');

$featCat = (string) $params->get('featured_filter_category', 'include'); // include|only|hide
$featSib = (string) $params->get('featured_filter_siblings', 'include'); // include|only|hide

$siblingsMode = (string) $params->get('siblings_mode', 'category'); // category|tags

// The field is a multi-select (array); a comma-separated string is still accepted.
$excludeParam = $params->get('exclude_category_ids', []);
$exclude = array_values(array_filter(array_map('intval', is_array($excludeParam)
    ? $excludeParam
    : preg_split('/\s*,\s*/', (string) $excludeParam, -1, PREG_SPLIT_NO_EMPTY))));

// Boot com_content MVC once
$component = $app->bootComponent('com_content');
$factory   = $component->getMVCFactory();

/**
 * An Articles model with the module's ordering, direction, featured filter and limit applied.
 * The Category model is not used for lists: it replaces list.ordering with the menu item's
 * ordering, so the module's own ordering options had no effect.
 */
$createListModel = function (string $orderBy, string $direction, string $featured, int $limit) use ($app, $factory) {
    $orderColumns = ['publish_up', 'created', 'modified', 'title', 'hits', 'ordering'];
    $featuredMap  = ['include' => 'show', 'only' => 'only', 'hide' => 'hide'];

    /** @var \Joomla\Component\Content\Site\Model\ArticlesModel $model */
    $model = $factory->createModel('Articles', 'Site', ['ignore_request' => true]);
    $model->setState('params', $app->getParams());
    $model->setState('filter.published', 1);
    $model->setState('filter.access', true);
    $model->setState('filter.language', Multilanguage::isEnabled());
    $model->setState('filter.featured', $featuredMap[$featured] ?? 'show');
    $model->setState('list.ordering', 'a.' . (in_array($orderBy, $orderColumns, true) ? $orderBy : 'publish_up'));
    $model->setState('list.direction', strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC');
    $model->setState('list.start', 0);
    $model->setState('list.limit', max(0, $limit));

    return $model;
};

if ($option === 'com_content' && $view === 'category' && $showCatView) {
    $catId  = $input->getInt('id');
    if ($catId) {
        $currentCategoryTitle = modCurrentCat_resolveCategoryTitle($option, $view, $input);

        if ($showSubSec) {
            // Children via Categories API
            $categories = Categories::getInstance('content');
            $node = $categories->get($catId);
            if ($node) {
                $depth = (int) $params->get('child_depth', 1);
                $limitSub = (int) $params->get('subcats_limit', 0);
                modCurrentCat_collectChildren($node, $depth, $exclude, $childCategories, $limitSub);
            }
        }

        if ($showCatArtSec) {
            $model = $createListModel($orderCat, $dirCat, $featCat, (int) $params->get('limit', 0));
            $model->setState('filter.category_id', (int) $catId);

            $articles = $model->getItems() ?: [];
        }
    }
} elseif ($option === 'com_content' && $view === 'article' && $showArtView) {
    $articleId = $input->getInt('id');
    $showSiblings = (int) $params->get('show_siblings', 1); // legacy param preserved
    if ($articleId && $showSiblings && $showSibSec) {
        /** @var \Joomla\Component\Content\Site\Model\ArticleModel $articleModel */
        $articleModel = $factory->createModel('Article', 'Site', ['ignore_request' => true]);
        $articleModel->setState('params', $app->getParams());
        $articleModel->setState('filter.published', 1);
        $articleModel->setState('filter.access', true);
        $articleModel->setState('article.id', (int) $articleId);

        $article = null;
        try {
            $article = $articleModel->getItem(); // reads id from state
        } catch (\Throwable $e) {
            $article = null;
        }

        if ($article) {
                $currentCategoryTitle = modCurrentCat_resolveCategoryTitle($option, $view, $input, $article);

            if ($siblingsMode === 'tags') {
                // Try to fetch related by shared tags
                $tagIds = [];
                try {
                    $db = Factory::getContainer()->get('DatabaseDriver');
                    // Get tag ids for current article from #__contentitem_tag_map
                    $query = $db->getQuery(true)
                        ->select($db->quoteName('tag_id'))
                        ->from($db->quoteName('#__contentitem_tag_map'))
                        ->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'))
                        ->where($db->quoteName('content_item_id') . ' = ' . (int) $articleId);
                    $db->setQuery($query);
                    $tagIds = (array) $db->loadColumn();
                } catch (\Throwable $e) {
                    $tagIds = [];
                }

                if (!empty($tagIds)) {
                    // Articles sharing any of the current article's tags, from any category
                    try {
                        $listModel = $createListModel($orderSib, $dirSib, $featSib, (int) $params->get('siblings_limit', 0));
                        $listModel->setState('filter.tag', array_map('intval', $tagIds));
                        // Exclude current
                        $listModel->setState('filter.article_id', (int) $articleId);
                        $listModel->setState('filter.article_id.include', false);
                        if (!empty($exclude)) {
                            $listModel->setState('filter.category_id', $exclude);
                            $listModel->setState('filter.category_id.include', false);
                        }
                        $siblingArticles = $listModel->getItems() ?: [];
                    } catch (\Throwable $e) {
                        // Fallback to category mode on error
                        $siblingsMode = 'category';
                    }
                } else {
                    $siblingsMode = 'category';
                }
            }

            if ($siblingsMode === 'category' && !empty($article->catid)) {
                $catModel = $createListModel($orderSib, $dirSib, $featSib, (int) $params->get('siblings_limit', 0));
                $catModel->setState('filter.category_id', (int) $article->catid);
                // Exclude current
                $catModel->setState('filter.article_id', (int) $articleId);
                $catModel->setState('filter.article_id.include', false);

                $siblingArticles = $catModel->getItems() ?: [];
            }
        }
    }
}

require ModuleHelper::getLayoutPath('mod_currentcat_children_and_articles', $params->get('layout', 'default'));
