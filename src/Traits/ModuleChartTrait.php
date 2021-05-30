<?php

/**
 * See LICENSE.md file for further details.
 */

declare(strict_types=1);

namespace LilaElephant\Webtrees\Topola\Traits;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Menu;

/**
 * Trait ModuleChartTrait.
 *
 * @author  Someone
 * @inspiration  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/PeWu/topola-webtrees
 */
trait ModuleChartTrait
{
    use \Fisharebest\Webtrees\Module\ModuleChartTrait;

    /**
     * CSS class for the URL.
     *
     * @return string
     */
    public function chartMenuClass(): string
    {
        return 'menu-branches';
    }

    public function chartBoxMenu(Individual $individual): ?Menu
    {
        return $this->chartMenu($individual);
    }

    public function chartUrl(Individual $individual, array $parameters = []): string
    {
        return route(self::ROUTE_DEFAULT, [
                'xref' => $individual->xref(),
                'tree' => $individual->tree()->name(),
            ] + $parameters);
    }
}
