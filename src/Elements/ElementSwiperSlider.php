<?php

namespace Antlion\SwiperSlider\Elements;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Antlion\SwiperSlider\Model\SlideImage;
use Antlion\SwiperSlider\Elements\ElementSwiperSliderController;

class ElementSwiperSlider extends BaseElement
{
    private static $table_name = 'ElementSwiperSlider';
    private static $icon = 'font-icon-block-carousel';

    private static $controller_class = ElementSwiperSliderController::class;

    private static $db = [
        'Effect'        => "Enum('slide,fade,coverflow,flip,cube,creative,cards','slide')",
        'Loop'          => 'Boolean',
        'Speed'         => 'Int',
        'Pagination'    => 'Boolean',
        'Navigation'    => 'Boolean',
        'Scrollbar'     => 'Boolean',
        'Autoplay'      => 'Boolean',
        'AutoplayDelay' => 'Int',
        'Lazy'          => 'Boolean',
        'AutoplayProgress' => 'Boolean',
    ];

    private static $has_many = [
        // IMPORTANT: use an explicit inverse name (see SlideImage change below)
        'Slides' => SlideImage::class . '.SliderElement',
    ];

    private static $owns = ['Slides'];

    private static $defaults = [
        'Speed' => 600,
        'Pagination' => 1,
        'Navigation' => 1,
        'Loop' => 1,
        'Autoplay' => 1,
        'AutoplayDelay' => 5000,
        'AutoplayProgress' => 1,
    ];

    public function getType()
    {
        return 'Swiper Slider';
    }

    public function getCMSFields(): FieldList
{
    $fields = parent::getCMSFields();

    // --- Slides grid (Main) ---
    $gridConfig = GridFieldConfig_RelationEditor::create();
    $gridConfig->addComponent(new GridFieldOrderableRows('SortOrder'));

    $slidesGrid = GridField::create('Slides', 'Slides', $this->Slides(), $gridConfig)
        ->setDescription('Drag to reorder slides.');

    // --- Settings fields ---
    $effect = DropdownField::create('Effect', 'Transition effect', [
        'slide'     => 'Slide',
        'fade'      => 'Fade',
        'coverflow' => 'Coverflow',
        'flip'      => 'Flip',
        'cube'      => 'Cube',
        'creative'  => 'Creative',
        'cards'     => 'Cards',
    ]);

    $speed = NumericField::create('Speed', 'Transition speed (ms)')
        ->setDescription('Example: 600');

    $loop = CheckboxField::create('Loop', 'Loop slides');

    $pagination = CheckboxField::create('Pagination', 'Show pagination dots');
    $navigation = CheckboxField::create('Navigation', 'Show prev/next arrows');
    $scrollbar  = CheckboxField::create('Scrollbar', 'Show scrollbar');

    $lazy = CheckboxField::create('Lazy', 'Lazy-load images')
        ->setDescription('Recommended for large hero images.');

    $autoplay = CheckboxField::create('Autoplay', 'Enable autoplay');

    $autoplayDelay = NumericField::create('AutoplayDelay', 'Autoplay delay (ms)')
        ->setDescription('Example: 5000 (5 seconds)')
        ->displayIf('Autoplay')->isChecked()->end();

    $autoplayProgress = CheckboxField::create('AutoplayProgress', 'Show autoplay progress indicator')
        ->displayIf('Autoplay')->isChecked()->end();

    // Group autoplay fields under a collapsible section
    $autoplayGroup = ToggleCompositeField::create(
        'AutoplayGroup',
        'Autoplay',
        [$autoplay, $autoplayDelay, $autoplayProgress]
    )->setHeadingLevel(4);

    // Group all settings under one collapsible section
    $settingsGroup = ToggleCompositeField::create(
        'SliderSettings',
        'Slider settings',
        [
            $effect,
            $speed,
            $loop,
            HeaderField::create('UiHeader', 'UI controls', 4),
            $pagination,
            $navigation,
            $scrollbar,
            HeaderField::create('PerfHeader', 'Performance', 4),
            $lazy,
            $autoplayGroup,
        ]
    );

    // Clean up: ensure we control ordering in Root.Main
    $fields->removeByName(['Effect','Loop','Speed','Pagination','Navigation','Scrollbar','Autoplay','AutoplayDelay','Lazy','AutoplayProgress','Slides']);

    // Put Slides first, settings below
    $fields->addFieldsToTab('Root.Main', [
        $slidesGrid,
        LiteralField::create('Spacer', '<hr />'),
        $settingsGroup,
    ]);

    return $fields;
}

    public function getSwiperOptions(): array
    {
        $o = [
            'effect' => $this->Effect ?: 'slide',
            'loop'   => (bool)$this->Loop,
            'speed'  => (int)($this->Speed ?: 600),
        ];

        if ($this->Pagination) $o['pagination'] = ['el'=>'.swiper-pagination','clickable'=>true];
        if ($this->Navigation) $o['navigation'] = ['nextEl'=>'.swiper-button-next','prevEl'=>'.swiper-button-prev'];
        if ($this->Scrollbar)  $o['scrollbar']  = ['el'=>'.swiper-scrollbar','hide'=>false];
        if ($this->Autoplay)   $o['autoplay']   = ['delay'=>(int)($this->AutoplayDelay ?: 5000),'disableOnInteraction'=>false,'pauseOnMouseEnter'=>true];

        if ($this->Lazy) {
            $o['preloadImages'] = false;
            $o['lazy'] = ['loadPrevNext'=>true,'loadOnTransitionStart'=>true];
        }

        return $o;
    }

    public function getSwiperOptionsJSON(): string
    {
        return json_encode($this->getSwiperOptions(), JSON_UNESCAPED_SLASHES);
    }

    public function getSlidesActive()
    {
        return $this->Slides()->where(SlideImage::activeFilterSQL());
    }
}
