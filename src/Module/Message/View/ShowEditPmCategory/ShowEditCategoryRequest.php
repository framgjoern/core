<?php

declare(strict_types=1);

namespace Stu\Module\Message\View\ShowEditPmCategory;

use Stu\Lib\Request\CustomControllerHelperTrait;
use Stu\Module\Message\View\ShowEditPmCategory\ShowEditCategoryRequestInterface;

final class ShowEditCategoryRequest implements ShowEditCategoryRequestInterface
{
    use CustomControllerHelperTrait;

    public function getCategoryId(): int
    {
        return $this->queryParameter('pmcat')->int()->defaultsTo(0);
    }
}
