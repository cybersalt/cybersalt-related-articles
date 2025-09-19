<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Component\Content\Site\Helper\RouteHelper;

$showHeadings = (int) $params->get('show_subheading', 1);
    $showCategoryNameAfter = (int) $params->get('show_category_name_after_heading', 0);
    $linkCategoryNameAfter = (int) $params->get('link_category_name_after_heading', 0);
    $catNameLink = '';
    if ($showCategoryNameAfter && !empty($currentCategoryTitle)) {
        // Best-effort: try to get the current cat id from input or inferred
        $catIdTmp = (int) ($input->getInt('id') ?: ($article->catid ?? 0));
        if ($catIdTmp) {
            $catNameLink = Route::_(RouteHelper::getCategoryRoute($catIdTmp, isset($article->language) ? $article->language : 0));
        }
    }

// Headings (preserve previous logic)
$headingSub = trim((string) $params->get('text_subcategories', ''));
if ($headingSub === '') {
    $headingSub = Text::_('MOD_CURRENTCAT_SUBCATEGORIES');
    if ($headingSub === 'MOD_CURRENTCAT_SUBCATEGORIES' || $headingSub === '') {
        $t = Text::_('JGLOBAL_SUBCATEGORIES');
        $headingSub = ($t !== 'JGLOBAL_SUBCATEGORIES' && $t !== '') ? $t : 'Subcategories';
    }
}
$headingArt = trim((string) $params->get('text_articles', ''));
if ($headingArt === '') {
    $headingArt = Text::_('MOD_CURRENTCAT_ARTICLES_IN_CATEGORY');
    if ($headingArt === 'MOD_CURRENTCAT_ARTICLES_IN_CATEGORY' || $headingArt === '') {
        $t = Text::_('JGLOBAL_ARTICLES');
        $headingArt = ($t !== 'JGLOBAL_ARTICLES' && $t !== '') ? $t : 'Articles in this Category';
    }
}
$headingSib = trim((string) $params->get('text_siblings', ''));
if ($headingSib === '') {
    $t = Text::_('MOD_CURRENTCAT_MORE_FROM_CATEGORY');
    $headingSib = ($t !== 'MOD_CURRENTCAT_MORE_FROM_CATEGORY' && $t !== '') ? $t : 'More from this category';
}

// Layout & CSS
$style = (string) $params->get('output_style', 'stack'); // stack|grid2|grid3
$cssEnable = (int) $params->get('css_enable', 1);
$textColor = trim((string) $params->get('css_text_color', ''));
$headingColor = trim((string) $params->get('css_heading_color', ''));
$linkColor = trim((string) $params->get('css_link_color', ''));
$linkHoverColor = trim((string) $params->get('css_link_hover_color', ''));
$dividerColor = trim((string) $params->get('css_divider_color', ''));
$customCss = (string) $params->get('css_custom', '');

$moduleId = isset($module) ? (int) $module->id : 0;
$rootId = 'mod-currentcat-' . $moduleId;

$rootClasses = 'mod-currentcat';
$rootClasses .= ' mod-currentcat--' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8');
?>
<?php if ($cssEnable || !empty($customCss)) : ?>
    <style>
        #<?php echo $rootId; ?> { <?php if ($textColor) : ?>color: <?php echo $textColor; ?>;<?php endif; ?> }
        #<?php echo $rootId; ?> .mod-currentcat__heading { <?php if ($headingColor) : ?>color: <?php echo $headingColor; ?>;<?php endif; ?> margin: 0 0 .5rem; font-weight: 600; }
        #<?php echo $rootId; ?> a { <?php if ($linkColor) : ?>color: <?php echo $linkColor; ?>;<?php endif; ?> text-decoration: none; }
        #<?php echo $rootId; ?> a:hover { <?php if ($linkHoverColor) : ?>color: <?php echo $linkHoverColor; ?>;<?php endif; ?> text-decoration: underline; }
        #<?php echo $rootId; ?> .mod-currentcat__list { display: grid; gap: .5rem; }
        #<?php echo $rootId; ?>.mod-currentcat--stack .mod-currentcat__item { padding: .35rem 0; <?php if ($dividerColor) : ?>border-bottom: 1px solid <?php echo $dividerColor; ?>;<?php else: ?>border-bottom: 1px solid rgba(0,0,0,.08);<?php endif; ?> }
        #<?php echo $rootId; ?>.mod-currentcat--stack .mod-currentcat__item:last-child { border-bottom: 0; }
        #<?php echo $rootId; ?>.mod-currentcat--grid2 .mod-currentcat__list { grid-template-columns: repeat(2, minmax(0,1fr)); }
        #<?php echo $rootId; ?>.mod-currentcat--grid3 .mod-currentcat__list { grid-template-columns: repeat(3, minmax(0,1fr)); }
        <?php if (!empty($customCss)) : ?>
        #<?php echo $rootId; ?> { }
        <?php echo $customCss; ?>
        <?php endif; ?>
    </style>
<?php endif; ?>

<div id="<?php echo $rootId; ?>" class="<?php echo $rootClasses; ?>">
    <?php if (!empty($childCategories) && $showSubSec) : ?>
        <?php if ($showHeadings) : ?>
            <h3 class="mod-currentcat__heading"><?php echo htmlspecialchars($headingSub, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if ($showCategoryNameAfter && !empty($currentCategoryTitle)) : ?>
                <div class="mod-currentcat__catname"><?php if ($linkCategoryNameAfter && !empty($catNameLink)) : ?><a href="<?php echo $catNameLink; ?>"><?php echo htmlspecialchars($currentCategoryTitle, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars($currentCategoryTitle, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="mod-currentcat__list mod-currentcat__subcategories">
            <?php foreach ($childCategories as $child) : ?>
                <div class="mod-currentcat__item mod-currentcat__subcategory">
                    <a class="mod-currentcat__link" href="<?php echo Route::_(RouteHelper::getCategoryRoute((int) $child->id, isset($child->language) ? $child->language : 0)); ?>">
                        <?php echo htmlspecialchars($child->title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($articles) && $showCatArtSec) : ?>
        <?php if ($showHeadings) : ?>
            <h3 class="mod-currentcat__heading"><?php echo htmlspecialchars($headingArt, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if ($showCategoryNameAfter && !empty($currentCategoryTitle)) : ?>
                <div class="mod-currentcat__catname"><?php if ($linkCategoryNameAfter && !empty($catNameLink)) : ?><a href="<?php echo $catNameLink; ?>"><?php echo htmlspecialchars($currentCategoryTitle, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars($currentCategoryTitle, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="mod-currentcat__list mod-currentcat__articles">
            <?php foreach ($articles as $article) : ?>
                <div class="mod-currentcat__item mod-currentcat__article">
                    <a class="mod-currentcat__link" href="<?php echo Route::_(RouteHelper::getArticleRoute((int) $article->id, (int) $article->catid, isset($article->language) ? $article->language : 0)); ?>">
                        <?php echo htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($siblingArticles) && $showSibSec) : ?>
        <?php if ($showHeadings) : ?>
            <h3 class="mod-currentcat__heading"><?php echo htmlspecialchars($headingSib, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if ($showCategoryNameAfter && !empty($currentCategoryTitle)) : ?>
                <div class="mod-currentcat__catname"><?php if ($linkCategoryNameAfter && !empty($catNameLink)) : ?><a href="<?php echo $catNameLink; ?>"><?php echo htmlspecialchars($currentCategoryTitle, ENT_QUOTES, 'UTF-8'); ?></a><?php else : ?><?php echo htmlspecialchars($currentCategoryTitle, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="mod-currentcat__list mod-currentcat__siblings">
            <?php foreach ($siblingArticles as $article) : ?>
                <div class="mod-currentcat__item mod-currentcat__sibling">
                    <a class="mod-currentcat__link" href="<?php echo Route::_(RouteHelper::getArticleRoute((int) $article->id, (int) $article->catid, isset($article->language) ? $article->language : 0)); ?>">
                        <?php echo htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
