<?php
namespace Antlion\SwiperSlider\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\File;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\CheckboxField;
use TractorCow\Colorpicker\Forms\ColorField;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Core\Validation\ValidationResult;
use Antlion\SwiperSlider\Elements\ElementSlider;

class SlideImage extends DataObject
{
    private static $table_name = 'SlideImage';
    private static $singular_name = 'Slide';
    private static $plural_name   = 'Slides';

    private static $db = [
        'Name'           => 'Varchar(255)',
        'Content'        => 'HTMLText',
        'Theme'          => 'Enum("light,dark","dark")',
        'Align'          => 'Enum("center,left,right","left")',
        'OverlayType'    => 'Enum("none,solid,gradient","solid")',
        'OverlayOpacity' => 'Int',
        'OverlayColor'   => 'Varchar(20)',
        'GradientDirection'     => 'Enum("right,left,bottom,top,bottom-right,bottom-left,top-right,top-left","right")',
        'GradientStartColor'    => 'Varchar(20)',
        'GradientStartOpacity'  => 'Int',
        'GradientStartPosition' => 'Int',
        'GradientEndColor'      => 'Varchar(20)',
        'GradientEndOpacity'    => 'Int',
        'GradientEndPosition'   => 'Int',
        'ContentBg'      => 'Boolean',
        'StartDate'      => 'Date',
        'EndDate'        => 'Date',
        'SortOrder'      => 'Int',
        'MediaType'      => 'Enum("image,video","image")',
        'VideoStart'     => 'Int',   // seconds
        'VideoEnd'       => 'Int',   // seconds (0 = full)
    ];

    private static $defaults = [
        'OverlayType'           => 'solid',
        'GradientDirection'     => 'right',
        'GradientStartColor'    => '000000',
        'GradientStartOpacity'  => 80,
        'GradientStartPosition' => 0,
        'GradientEndColor'      => '000000',
        'GradientEndOpacity'    => 0,
        'GradientEndPosition'   => 100,
    ];

    private static $has_one = [
        'Image'       => Image::class,
        'MobileImage' => Image::class,
        'VideoMP4'    => File::class,
        'VideoWebM'   => File::class,
        'VideoPoster' => Image::class,
        'Parent'      => SiteTree::class,
        'CoverLink'   => Link::class,
        'ElementSlider'   => ElementSlider::class,
    ];

    private static $has_many = [
        'Links' => Link::class . '.Owner',
    ];

    private static $owns = [
        'Image',
        'MobileImage',
        'VideoMP4',
        'VideoWebM',
        'VideoPoster',
        'Links',
        'CoverLink',
    ];

    private static $default_sort = 'SortOrder';

    private static $summary_fields = [
        'CMSThumb'           => 'Preview',
        'Name'               => 'Name',
        'MediaType'          => 'Type',
        'StartDate'          => 'Starts',
        'EndDate'            => 'Ends',
    ];

    public function getCMSThumb()
    {
        if ($this->MediaType === 'video') {
            return $this->VideoPoster()->exists()
                ? $this->VideoPoster()->CMSThumbnail()
                : '(video)';
        }
        return $this->Image()->exists() ? $this->Image()->CMSThumbnail() : '';
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Links',
            'SortOrder',
            'ParentID',
            'Theme',
            'Align',
            'OverlayType',
            'OverlayOpacity',
            'OverlayColor',
            'GradientDirection',
            'GradientStartColor',
            'GradientStartOpacity',
            'GradientStartPosition',
            'GradientEndColor',
            'GradientEndOpacity',
            'GradientEndPosition',
            'ContentBg',
            'StartDate',
            'EndDate',
            'CoverLinkID',
            'VideoStart',
            'VideoEnd',
            'ElementSlider'
        ]);

        // Media toggle (before image)
        $fields->insertBefore(
            'Image',
            DropdownField::create('MediaType', 'Media type (Choose Image or Video)', [
                'image' => 'Image',
                'video' => 'Video (HTML5)',
            ])
        );

        // Images
        $fields->replaceField(
            'Image',
            UploadField::create('Image', 'Desktop image')
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('swiper/slides')
                ->setDescription('Optimal 1920x700')
                ->displayIf('MediaType')->isEqualTo('image')->end()
        );
        $fields->replaceField(
            'MobileImage',
            UploadField::create('MobileImage', 'Mobile image')
                ->setAllowedFileCategories('image/supported')
                ->setFolderName('swiper/slides')
                ->setDescription('Optional; fallback is desktop image. Optimal 960×1024')
                ->displayIf('MediaType')->isEqualTo('image')->end()
        );

        // Videos
        $mp4    = UploadField::create('VideoMP4', 'Video (MP4)');
        $webm   = UploadField::create('VideoWebM', 'Video (WebM, optional)');
        $poster = UploadField::create('VideoPoster', 'Poster image (optional)');

        $mp4->getValidator()->setAllowedExtensions(['mp4']);
        $webm->getValidator()->setAllowedExtensions(['webm']);

        $mp4->displayIf('MediaType')->isEqualTo('video')->end();
        $webm->displayIf('MediaType')->isEqualTo('video')->end();
        $poster->displayIf('MediaType')->isEqualTo('video')->end();

        $start = NumericField::create('VideoStart', 'Video Start (seconds)')
            ->displayIf('MediaType')->isEqualTo('video')->end();
        $end = NumericField::create('VideoEnd', 'Video End (seconds, 0 = full)')
            ->displayIf('MediaType')->isEqualTo('video')->end();

        $fields->addFieldsToTab('Root.Main', [$mp4, $webm, $poster, $start, $end]);

        // Appearance
        $fields->addFieldToTab(
            'Root.Main',
            FieldGroup::create(
                'Appearance',
                DropdownField::create('Theme', 'Theme', [
                    'light' => 'Light',
                    'dark'  => 'Dark',
                ]),
                DropdownField::create('Align', 'Content alignment', [
                    'left'   => 'Left',
                    'right'  => 'Right',
                    'center' => 'Center',
                ]),
                CheckboxField::create('ContentBg', 'Add Content Background Overlay')
                    ->setDescription('Add background for content'),
            )->setName('AppearanceGroup')->addExtraClass('stack')
        );

        // Overlay: none, a solid color, or a gradient
        $fields->addFieldsToTab('Root.Main', [
            DropdownField::create('OverlayType', 'Overlay', [
                'none'     => 'None',
                'solid'    => 'Solid color',
                'gradient' => 'Gradient',
            ]),
            Wrapper::create(
                FieldGroup::create(
                    'Solid overlay',
                    NumericField::create('OverlayOpacity', 'Opacity (0–100)')
                        ->setDescription('Typical: 0–70'),
                    ColorField::create('OverlayColor', 'Color')
                        ->setDescription('Optional. Defaults to black for the Dark theme, white for Light.')
                )->setName('SolidOverlayGroup')
            )->displayIf('OverlayType')->isEqualTo('solid')->end(),
            Wrapper::create(
                DropdownField::create('GradientDirection', 'Gradient direction', [
                    'right'        => 'Left → Right',
                    'left'         => 'Right → Left',
                    'bottom'       => 'Top → Bottom',
                    'top'          => 'Bottom → Top',
                    'bottom-right' => 'Top left → Bottom right',
                    'bottom-left'  => 'Top right → Bottom left',
                    'top-right'    => 'Bottom left → Top right',
                    'top-left'     => 'Bottom right → Top left',
                ])->setDescription('The gradient runs from its start color to its end color in this direction.'),
                FieldGroup::create(
                    'Gradient start',
                    ColorField::create('GradientStartColor', 'Color'),
                    NumericField::create('GradientStartOpacity', 'Opacity (0–100)'),
                    NumericField::create('GradientStartPosition', 'Position (0–100%)')
                )->setName('GradientStartGroup')
                    ->setDescription('Position is where the fade begins; before it the start color is solid.'),
                FieldGroup::create(
                    'Gradient end',
                    ColorField::create('GradientEndColor', 'Color'),
                    NumericField::create('GradientEndOpacity', 'Opacity (0–100)'),
                    NumericField::create('GradientEndPosition', 'Position (0–100%)')
                )->setName('GradientEndGroup')
                    ->setDescription('Position is where the fade ends; after it the end color is solid.')
            )->displayIf('OverlayType')->isEqualTo('gradient')->end(),
        ]);

        // Schedule
        $fields->addFieldToTab(
            'Root.Main',
            FieldGroup::create(
                'Schedule',
                DateField::create('StartDate', 'Start date')
                    ->setHTML5(true)
                    ->setDescription('Optional. Slide visible on/after this date.'),
                DateField::create('EndDate', 'End date')
                    ->setHTML5(true)
                    ->setDescription('Optional. Slide remains visible through this date.')
            )->setName('ScheduleGroup')->addExtraClass('stack')
        );

        // Buttons (many)
        $buttonLinkField = MultiLinkField::create('Links', 'Buttons')
            ->setDescription('Button links placed in the slide');

        // Cover link (single)
        $coverLinkField = LinkField::create('CoverLink', 'Cover link')
            ->setDescription('Optional: a link that wraps the entire slide');

        // Mutually exclusive hints
        $hasCoverLink = (bool)$this->CoverLinkID;
        $hasButtons   = (bool)$this->Links()->exists();
        if ($hasCoverLink) {
            $buttonLinkField->setDisabled(true)
                ->setDescription('Disabled because a Cover Link is set. Remove it to enable Buttons.');
        } elseif ($hasButtons) {
            $coverLinkField->setDisabled(true)
                ->setDescription('Disabled because Buttons exist. Remove all buttons to enable Cover Link.');
        }

        $fields->addFieldToTab('Root.Main', $buttonLinkField);
        $fields->addFieldToTab('Root.Main', $coverLinkField);

        // Simple conditional help (no hard hide/show in core)
        if ($this->MediaType === 'image') {
            $mp4->setDescription('Switch Media type to “Video (HTML5)” to use these.');
            $webm->setDescription('Switch Media type to “Video (HTML5)” to use these.');
        }

        return $fields;
    }

    public function OverlayOpacityCss(): string
    {
        $pct = max(0, min(100, (int)$this->OverlayOpacity));
        return (string) round($pct / 100, 2);
    }

    /**
     * Whether the slide renders a .swiper-overlay at all.
     */
    public function ShowOverlay(): bool
    {
        return $this->OverlayType !== 'none';
    }

    /**
     * Inline style for .swiper-overlay. Solid: opacity, plus the color when one is set
     * (otherwise the Theme's --overlay-bg applies). Gradient: a linear-gradient background
     * at full opacity, since each end carries its own alpha.
     */
    public function OverlayStyle(): string
    {
        if ($this->OverlayType === 'gradient') {
            return '--overlay: 1; background: ' . $this->GradientCss() . ';';
        }

        $parts = [];
        if ($this->OverlayOpacity) {
            $parts[] = '--overlay: ' . $this->OverlayOpacityCss();
        }
        if ($color = $this->OverlayColorCss()) {
            $parts[] = '--overlay-bg: ' . $color;
        }
        return $parts ? implode('; ', $parts) . ';' : '';
    }

    /**
     * OverlayColor as "#rrggbb", or empty when unset or not a valid hex color.
     */
    public function OverlayColorCss(): string
    {
        $rgb = $this->hexToRgb((string)$this->OverlayColor);
        return $rgb ? vsprintf('#%02x%02x%02x', $rgb) : '';
    }

    /**
     * e.g. "linear-gradient(to right, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 100%)"
     */
    public function GradientCss(): string
    {
        $direction = 'to ' . str_replace('-', ' ', $this->GradientDirection ?: 'right');

        return sprintf(
            'linear-gradient(%s, %s %d%%, %s %d%%)',
            $direction,
            $this->gradientStop((string)$this->GradientStartColor, (int)$this->GradientStartOpacity),
            $this->clampPercent($this->GradientStartPosition),
            $this->gradientStop((string)$this->GradientEndColor, (int)$this->GradientEndOpacity),
            $this->clampPercent($this->GradientEndPosition)
        );
    }

    /**
     * rgba() for one end of the gradient; an unset color falls back to black.
     */
    private function gradientStop(string $hex, int $opacity): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex) ?: [0, 0, 0];
        return sprintf('rgba(%d, %d, %d, %s)', $r, $g, $b, round($this->clampPercent($opacity) / 100, 2));
    }

    private function clampPercent($value): int
    {
        return max(0, min(100, (int)$value));
    }

    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public function IsActive(): bool
    {
        $today = DBDatetime::now()->DateString(); // YYYY-MM-DD
        $start = $this->StartDate ?: null;
        $end   = $this->EndDate ?: null;

        if (!$start && !$end) return true;
        if ($start && !$end)  return $start <= $today;
        if (!$start && $end)  return $today <= $end;
        return $start <= $today && $today <= $end;
    }

    public static function activeFilterSQL(): string
    {
        return '("StartDate" IS NULL OR "StartDate" <= CURRENT_DATE())'
             . ' AND ("EndDate" IS NULL OR "EndDate" >= CURRENT_DATE())';
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->StartDate && $this->EndDate && $this->EndDate < $this->StartDate) {
            $this->EndDate = $this->StartDate;
        }
        $this->OverlayOpacity = max(0, min(100, (int)$this->OverlayOpacity));
        foreach (['GradientStartOpacity', 'GradientStartPosition', 'GradientEndOpacity', 'GradientEndPosition'] as $field) {
            $this->$field = $this->clampPercent($this->$field);
        }
    }

    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if ($this->CoverLinkID && $this->Links()->exists()) {
            $result->addError('Choose either a Cover Link or Buttons, not both.');
        }

        if ($this->MediaType === 'image' && !$this->ImageID) {
            $result->addError('Please upload a Desktop image (or switch Media type to Video).');
        }

        if ($this->MediaType === 'video'
            && !$this->VideoMP4ID
            && !$this->VideoWebMID
        ) {
            $result->addError('Please upload at least an MP4 or WebM for the video slide.');
        }

        if ($this->VideoStart && $this->VideoEnd && $this->VideoEnd < $this->VideoStart) {
            $result->addError('Video End must be greater than or equal to Start.');
        }

        return $result;
    }


    // Helpers used by the template
    public function getIsVideo(): bool
    {
        return $this->MediaType === 'video';
    }

    public function getPosterURL(): ?string
    {
        if (!$this->VideoPoster()->exists()) return null;
        [$w, $h] = $this->resolveDesktopDimensions();
        return $this->VideoPoster()->FocusFill($w, $h)->getURL();
    }

    /**
     * CSS custom properties so video slides keep the slider's desktop/mobile aspect ratios.
     */
    public function getMediaAspectStyle(): string
    {
        [$dw, $dh] = $this->resolveDesktopDimensions();
        [$mw, $mh] = $this->resolveMobileDimensions();
        return sprintf('--ar-desktop: %d / %d; --ar-mobile: %d / %d;', $dw, $dh, $mw, $mh);
    }

    public function getDesktopImageURL(): string
    {
        if (!$this->Image()->exists()) return '';
        [$w, $h] = $this->resolveDesktopDimensions();
        return $this->Image()->FocusFill($w, $h)->getURL();
    }

    public function getMobileImageURL(): string
    {
        $img = $this->MobileImage()->exists() ? $this->MobileImage() : $this->Image();
        if (!$img->exists()) return '';
        [$w, $h] = $this->resolveMobileDimensions();
        return $img->FocusFill($w, $h)->getURL();
    }

    private function resolveDesktopDimensions(): array
    {
        if ($this->ElementSliderID && $this->ElementSlider()->exists()) {
            $s = $this->ElementSlider();
            return [(int)($s->DesktopWidth ?: 1920), (int)($s->DesktopHeight ?: 700)];
        }
        if ($this->ParentID && ($p = $this->Parent()) && $p->exists()) {
            return [(int)($p->DesktopWidth ?: 1920), (int)($p->DesktopHeight ?: 700)];
        }
        return [1920, 700];
    }

    private function resolveMobileDimensions(): array
    {
        if ($this->ElementSliderID && $this->ElementSlider()->exists()) {
            $s = $this->ElementSlider();
            return [(int)($s->MobileWidth ?: 960), (int)($s->MobileHeight ?: 1024)];
        }
        if ($this->ParentID && ($p = $this->Parent()) && $p->exists()) {
            return [(int)($p->MobileWidth ?: 960), (int)($p->MobileHeight ?: 1024)];
        }
        return [960, 1024];
    }

    public function canView($member = null)
    {
        return $this->extendedCan(__FUNCTION__, $member) ?? $this->parentCan(__FUNCTION__, $member);
    }

    public function canEdit($member = null)
    {
        return $this->extendedCan(__FUNCTION__, $member) ?? $this->parentCan(__FUNCTION__, $member);
    }

    public function canDelete($member = null)
    {
        return $this->extendedCan(__FUNCTION__, $member) ?? $this->parentCan(__FUNCTION__, $member);
    }

    public function canCreate($member = null, $context = [])
    {
        return $this->extendedCan(__FUNCTION__, $member, $context) ?? Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }

    /**
     * Slides follow the permissions of their slider block, or the page they were added to directly.
     */
    private function parentCan(string $method, $member): bool
    {
        $parent = $this->ElementSlider()->exists() ? $this->ElementSlider() : $this->Parent();
        return $parent->exists() ? $parent->$method($member) : Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
    }
}
