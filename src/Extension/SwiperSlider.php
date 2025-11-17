<?php
namespace Antlion\SwiperSlider\Extension;

use Antlion\SwiperSlider\Model\SlideImage;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\ORM\DataList;

class SwiperSlider extends Extension
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
    ];

    private static $has_many = [
        'Slides' => SlideImage::class,
    ];

    private static $owns = [
        'Slides',
    ];

    public function populateDefaults(): void
    {
        $this->owner->Speed         = 600;
        $this->owner->Pagination    = true;
        $this->owner->Navigation    = true;
        $this->owner->Loop          = true;
        $this->owner->Autoplay      = true;
        $this->owner->AutoplayDelay = 5000;

        parent::populateDefaults();
    }

    public function updateCMSFields(FieldList $fields): void
    {
        // Ensure HeroSlider tab exists
        if (!$fields->fieldByName('Root.HeroSlider')) {
            $fields->addFieldToTab('Root', Tab::create('HeroSlider'));
        }

        // Remove any scaffolded fields so we control layout
        $fields->removeByName([
            'Slides',
            'Effect',
            'Loop',
            'Speed',
            'Pagination',
            'Navigation',
            'Scrollbar',
            'Autoplay',
            'AutoplayDelay',
            'Lazy',
        ]);

        // Slides GridField (single, orderable)
        $gridConfig = GridFieldConfig_RelationEditor::create();
        $gridConfig->addComponent(new GridFieldOrderableRows('SortOrder'));

        $slidesGrid = GridField::create(
            'Slides',
            'Slides',
            $this->owner->Slides(),
            $gridConfig
        );

        $fields->addFieldToTab('Root.HeroSlider', $slidesGrid);

        // Slider Settings – IMPORTANT: FieldList::create(), not a raw array
        $sliderSettingsFields = FieldList::create(
            DropdownField::create('Effect', 'Effect', [
                'slide'    => 'Slide',
                'fade'     => 'Fade',
                'coverflow'=> 'Coverflow',
                'flip'     => 'Flip',
                'cube'     => 'Cube',
                'creative' => 'Creative',
                'cards'    => 'Cards',
            ]),
            CheckboxField::create('Loop', 'Loop'),
            CheckboxField::create('Pagination', 'Pagination'),
            CheckboxField::create('Navigation', 'Navigation (prev/next)'),
            CheckboxField::create('Scrollbar', 'Scrollbar'),
            CheckboxField::create('Lazy', 'Lazy images'),
            CheckboxField::create('Autoplay', 'Autoplay'),
            NumericField::create('AutoplayDelay', 'Autoplay delay (ms)'),
            NumericField::create('Speed', 'Transition speed (ms)')
                ->setDescription('Transition duration in ms')
        );

        $fields->addFieldToTab(
            'Root.HeroSlider',
            ToggleCompositeField::create(
                'SliderSettings',
                'Slider Settings',
                $sliderSettingsFields
            )->setStartClosed(false)
        );
    }

    public function getSwiperOptions(): array
    {
        $o = [
            'effect' => $this->owner->Effect ?: 'slide',
            'loop'   => (bool) $this->owner->Loop,
            'speed'  => (int) ($this->owner->Speed ?: 600),
        ];

        if ($this->owner->Pagination) {
            $o['pagination'] = [
                'el'        => '.swiper-pagination',
                'clickable' => true,
            ];
        }

        if ($this->owner->Navigation) {
            $o['navigation'] = [
                'nextEl' => '.swiper-button-next',
                'prevEl' => '.swiper-button-prev',
            ];
        }

        if ($this->owner->Scrollbar) {
            $o['scrollbar'] = [
                'el'   => '.swiper-scrollbar',
                'hide' => false,
            ];
        }

        if ($this->owner->Autoplay) {
            $o['autoplay'] = [
                'delay'               => (int) ($this->owner->AutoplayDelay ?: 5000),
                'disableOnInteraction'=> false,
                'pauseOnMouseEnter'   => true,
            ];
        }

        if ($this->owner->Lazy) {
            $o['preloadImages'] = false;
            $o['lazy'] = [
                'loadPrevNext'          => true,
                'loadOnTransitionStart' => true,
            ];
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
        if (!$list) {
            return SlideImage::get()->where('1 = 0');
        }
        return $list->where(SlideImage::activeFilterSQL());
    }
}
