<?php

/**
 * See LICENSE.md file for further details.
 */

declare(strict_types=1);

namespace LilaElephant\Webtrees\Topola;

use Aura\Router\RouterContainer;
use Exception;
use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Exceptions\IndividualNotFoundException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleChartInterface;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleThemeInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\View;
use LilaElephant\Webtrees\Topola\Traits\ModuleChartTrait;
use LilaElephant\Webtrees\Topola\Traits\ModuleCustomTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fisharebest\Webtrees\Services\GedcomExportService;

/**
 * Topola module class.
 * @author  Someone
 * @inspiration  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link    https://github.com/PeWu/topola-webtrees/
 */
class Module extends AbstractModule implements ModuleCustomInterface, ModuleChartInterface, RequestHandlerInterface
{
    use ModuleCustomTrait;
    use ModuleChartTrait;

    private const ROUTE_DEFAULT     = 'topola';
    private const ROUTE_DEFAULT_URL = '/tree/{tree}/topola/{xref}';

    /**
     * @var string
     */
    private const GITHUB_REPO = 'PeWu/topola-webtrees';

    /**
     * @var string
     */
    public const CUSTOM_AUTHOR = 'PeWu';

    /**
     * @var string
     */
    public const CUSTOM_VERSION = '2.2.0';

    /**
     * @var string
     */
    public const CUSTOM_SUPPORT_URL = 'https://github.com/' . self::GITHUB_REPO . '/issues';

    /**
     * @var string
     */
    public const CUSTOM_LATEST_VERSION = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';

    /**
     * The current theme instance.
     *
     * @var ModuleThemeInterface
     */
    private $theme;

    /**
     * Initialization.
     */
    public function boot(): void
    {
        /** @var RouterContainer $routerContainer */
        $routerContainer = Registry::container()->get(RouterContainer::class);

        $routerContainer->getMap()
            ->get(self::ROUTE_DEFAULT, self::ROUTE_DEFAULT_URL, $this)
            ->allows(RequestMethodInterface::METHOD_POST);

        $this->theme = Registry::container()->get(ModuleThemeInterface::class);

        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

    /**
     * How should this module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string
    {
        return I18N::translate('Interactive tree (Topola)');
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {
        return I18N::translate('Opens the Topola Genealogy Viewer interactive familty tree.');
    }

    /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/../resources/';
    }

    /**
     * Handles a request and produces a response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree       = $request->getAttribute('tree');
        $user       = $request->getAttribute('user');
        $xref       = $request->getAttribute('xref');
        $individual = Registry::individualFactory()->make($xref, $tree);

        if ($individual === null) {
            throw new IndividualNotFoundException();
        }

        // Convert POST requests into GET requests for pretty URLs.
        // This also updates the name above the form, which wont get updated if only a POST request is used
        if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
            $params = (array) $request->getParsedBody();

            return redirect(route(self::ROUTE_DEFAULT, [
                'tree'                    => $tree->name(),
                'xref'                    => $params['xref']
            ]));
        }

        Auth::checkIndividualAccess($individual, false, true);
        Auth::checkComponentAccess($this, 'chart', $tree, $user);

        $ajaxUrl = route('module', [
            'module' => $this->name(),
            'action' => 'gedcom',
            'tree'   => $individual->tree()->name(),
            'xref'   => $individual->xref(),
        ]);

        return $this->viewResponse(
            $this->name() . '::chart',
            [
                'title'         => $this->getPageTitle($individual),
                'moduleName'    => $this->name(),
                'ajaxUrl'       => $ajaxUrl,
                'individual'    => $individual,
                'tree'          => $tree,
                'javascript'    => $this->assetUrl('js/topola.js'),
            ]
        );
    }

    /**
     * Returns the page title.
     *
     * @param Individual $individual The individual used in the current chart
     *
     * @return string
     */
    private function getPageTitle(Individual $individual): string
    {
        $title = I18N::translate('Interactive tree (Topola)');

        if ($individual->canShowName()) {
            $title = I18N::translate('Interactive tree (Topola): %s', $individual->fullName());
        }

        return $title;
    }

    /**
     * Gedcom action.
     *
     * @param ServerRequestInterface $request The current HTTP request
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    public function getGedcomAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree         = $request->getAttribute('tree');
        $user         = $request->getAttribute('user');
        $xref         = $request->getQueryParams()['xref'];
        $individual   = Registry::individualFactory()->make($xref, $tree);

        Auth::checkIndividualAccess($individual, false, true);
        Auth::checkComponentAccess($this, 'chart', $tree, $user);

        $stream = fopen('php://output', 'w');
        $service = Registry::container()->get(GedcomExportService::class);
        $output = $service->export($tree, false, 'UTF-8', Auth::accessLevel($tree), 'CRLF');
        stream_copy_to_stream($output, $stream);
        return response(
            $this->getGedcomRoute($individual)
            );
    }

    /**
     * Get the raw gedcom URL. The "xref" parameter must be the last one as the URL gets appended
     * with the clicked individual id in order to load the required chart data.
     *
     * @param Individual $individual
     *
     * @return string
     */
    private function getGedcomRoute(Individual $individual): string
    {
        return route('module', [
            'module'      => $this->name(),
            'action'      => 'gedcom',
            'xref'        => $individual->xref(),
            'tree'        => $individual->tree()->name(),
        ]);
    }
}
