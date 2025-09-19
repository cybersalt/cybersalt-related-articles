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
use Joomla\CMS\Language\Text;
use Joomla\Component\Content\Site\Helper\RouteHelper;

$app   = Factory::getApplication();
$input = $app->getInput();

$option = $input->getCmd('option');
$view   = $input->getCmd('view');

$childCategories = [];
$currentCategoryTitle = '';
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

$articles        = [];
$siblingArticles = [];

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

// Boot com_content MVC once
$component = $app->bootComponent('com_content');
$factory   = $component->getMVCFactory();

// Helpers to map featured filter
$getFeaturedState = function(string $mode) {
    switch ($mode) {
        case 'only': return 1;
        case 'hide': return 0;
        default: return ''; // include
    }
};
$mapOrderField = function(string $f) {
    // Joomla list states commonly: a.publish_up, a.created, a.modified, a.title, a.hits, a.ordering
    // Use raw names and let model map/validate.
    return $f;
};

if ($option === 'com_content' && $view === 'category' && $showCatView) {
    $catId  = $input->getInt('id');
    if ($catId) {
        if ($showSubSec) {
            // Children via Categories API
            $categories = Categories::getInstance('content');
            $node = $categories->get($catId);
            if ($node) {
                $currentCategoryTitle = modCurrentCat_resolveCategoryTitle($option, $view, $input);
                $childCategories = [];
$currentCategoryTitle = '';
            $depth = (int) $params->get('child_depth', 1);
            $exclude = array_filter(array_map('intval', preg_split('/\s*,\s*/', (string) $params->get('exclude_category_ids', ''), -1, PREG_SPLIT_NO_EMPTY)));
            $limitSub = (int) $params->get('subcats_limit', 0);
            modCurrentCat_collectChildren($node, $depth, $exclude, $childCategories, $limitSub);

            }
        }

        if ($showCatArtSec) {
            /** @var \Joomla\Component\Content\Site\Model\CategoryModel $model */
            $model = $factory->createModel('Category', 'Site', ['ignore_request' => true]);
            $model->setState('category.id', $catId);
            $model->setState('filter.published', 1);
            $model->setState('filter.access', true);
            $model->setState('params', $app->getParams());
            $model->setState('list.start', 0);
            $limit = (int) $params->get('limit', 0);
            if ($limit > 0) {
                $model->setState('list.limit', $limit);
            }
            // Featured filter
            $feat = $getFeaturedState($featCat);
            if ($feat !== '') {
                $model->setState('filter.featured', $feat);
            }
            // Ordering
            $model->setState('list.ordering', $mapOrderField($orderCat));
            $model->setState('list.direction', $dirCat);

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
                    // Use com_content "Category" model scoped to all categories but filter by tags via join
                    try {
                        /** @var \Joomla\Component\Content\Site\Model\ArticlesModel $listModel */
                        $listModel = $factory->createModel('Articles', 'Site', ['ignore_request' => true]);
                        $listModel->setState('filter.published', 1);
                        $listModel->setState('filter.access', true);
                        $listModel->setState('params', $app->getParams());
                        // The ArticlesModel supports 'filter.tag' in J5; set if present
                        $listModel->setState('filter.tag', $tagIds);
                        // Exclude current
                        $listModel->setState('filter.article_id', $articleId);
                        $listModel->setState('filter.article_id.include', false);
                        // Featured
                        $feat = $getFeaturedState($featSib);
                        if ($feat !== '') {
                            $listModel->setState('filter.featured', $feat);
                        }
                        // Order
                        $listModel->setState('list.ordering', $mapOrderField($orderSib));
                        $listModel->setState('list.direction', $dirSib);
                        $sLimit = (int) $params->get('siblings_limit', 0);
                        if ($sLimit > 0) {
                            $listModel->setState('list.limit', $sLimit);
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
                $catId = (int) $article->catid;
                /** @var \Joomla\Component\Content\Site\Model\CategoryModel $catModel */
                $catModel = $factory->createModel('Category', 'Site', ['ignore_request' => true]);
                $catModel->setState('category.id', $catId);
                $catModel->setState('filter.published', 1);
                $catModel->setState('filter.access', true);
                $catModel->setState('params', $app->getParams());
                $catModel->setState('list.start', 0);
                $sLimit = (int) $params->get('siblings_limit', 0);
                if ($sLimit > 0) {
                    $catModel->setState('list.limit', $sLimit + 1); // +1 to account for excluding current
                }
                // Featured
                $feat = $getFeaturedState($featSib);
                if ($feat !== '') {
                    $catModel->setState('filter.featured', $feat);
                }
                // Order
                $catModel->setState('list.ordering', $mapOrderField($orderSib));
                $catModel->setState('list.direction', $dirSib);

                $items = $catModel->getItems() ?: [];
                foreach ($items as $it) {
                    if ((int) $it->id === (int) $articleId) {
                        continue; // exclude current
                    }
                    $siblingArticles[] = $it;
                    if ($sLimit > 0 && count($siblingArticles) >= $sLimit) {
                        break;
                    }
                }
            }
        }
    }
}

require ModuleHelper::getLayoutPath('mod_currentcat_children_and_articles', $params->get('layout', 'default'));
