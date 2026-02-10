<?php
namespace Antlion\SwiperSlider\Extension;

use Antlion\SwiperSlider\Model\SlideImage;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextCheckboxGroupField;
use SilverStripe\Forms\Field;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class SwiperSlider extends DataExtension
{
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

    private static $has_many = ['Slides' => SlideImage::class];
    private static $owns     = ['Slides'];

    public function populateDefaults(): void
    {
        $this->owner->Speed = 600;
        $this->owner->Pagination = true;
        $this->owner->Navigation = true;
        $this->owner->Loop = true;
        $this->owner->Autoplay = true;
        $this->owner->AutoplayDelay = 5000;
        $this->owner->AutoplayProgress = true;
    }

    public function updateCMSFields(FieldList $fields): void
    {
        // Ensure tab exists
        if (!$fields->fieldByName('Root.HeroSlider')) {
            $fields->addFieldToTab('Root', Tab::create('HeroSlider'));
        }

        // Remove auto-generated fields (so we control layout)
        $fields->removeByName([
            'Effect','Loop','Pagination','Navigation','Scrollbar','Lazy',
            'Autoplay','AutoplayDelay','AutoplayProgress','Speed','Slides','SliderSettings'
        ]);

        // Slides grid (single instance)
        $gridConfig = GridFieldConfig_RelationEditor::create();
        $gridConfig->addComponent(new GridFieldOrderableRows('SortOrder'));

        $slidesGrid = GridField::create(
            'Slides',
            'Slides',
            $this->owner->Slides(),
            $gridConfig
        );

        $fields->addFieldToTab('Root.HeroSlider', $slidesGrid);

        // Settings (single instance)
        $fields->addFieldToTab(
            'Root.HeroSlider',
            ToggleCompositeField::create('SliderSettings', 'Slider Settings', [
                DropdownField::create('Effect', 'Effect', [
                    'slide'=>'Slide','fade'=>'Fade','coverflow'=>'Coverflow','flip'=>'Flip',
                    'cube'=>'Cube','creative'=>'Creative','cards'=>'Cards',
                ]),
                CheckboxField::create('Loop', 'Loop'),
                CheckboxField::create('Pagination', 'Pagination'),
                CheckboxField::create('Navigation', 'Navigation (prev/next)'),
                CheckboxField::create('Scrollbar', 'Scrollbar'),
                CheckboxField::create('Lazy', 'Lazy images'),
                CheckboxField::create('Autoplay', 'Autoplay'),
                CheckboxField::create('AutoplayProgress', 'Show autoplay progress'),
                NumericField::create('AutoplayDelay', 'Autoplay delay (ms)'),
                NumericField::create('Speed', 'Transition speed (ms)'),
            ])->setStartClosed(false)
        );
    }

    public function getSwiperOptions(): array
    {
        $o = [
            'effect' => $this->owner->Effect ?: 'slide',
            'loop'   => (bool)$this->owner->Loop,
            'speed'  => (int)($this->owner->Speed ?: 600),
        ];
        if ($this->owner->Pagination) $o['pagination'] = ['el'=>'.swiper-pagination','clickable'=>true];
        if ($this->owner->Navigation) $o['navigation'] = ['nextEl'=>'.swiper-button-next','prevEl'=>'.swiper-button-prev'];
        if ($this->owner->Scrollbar)  $o['scrollbar']  = ['el'=>'.swiper-scrollbar','hide'=>false];
        if ($this->owner->Autoplay)   $o['autoplay']   = ['delay'=>(int)($this->owner->AutoplayDelay ?: 5000),'disableOnInteraction'=>false,'pauseOnMouseEnter'=>true];
        if ($this->owner->Lazy) {
            $o['preloadImages'] = false;
            $o['lazy'] = ['loadPrevNext'=>true,'loadOnTransitionStart'=>true];
        }
        return $o;
    }

    public function getSwiperOptionsJSON(): string
    {
        return json_encode($this->getSwiperOptions(), JSON_UNESCAPED_SLASHES);
    }

    public function getHasSlides(): bool
    {
        $slides = $this->owner->Slides();
        return $slides && $slides->exists();
    }

    public function getSlidesActive(): DataList
    {
        $list = $this->owner->Slides();
        if (!$list) return SlideImage::get()->where('1 = 0');
        return $list->where(SlideImage::activeFilterSQL());
    }
}
