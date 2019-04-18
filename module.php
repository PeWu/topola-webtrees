<?php
namespace Fisharebest\Webtrees;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Controller\ChartController;
use Fisharebest\Webtrees\Functions\FunctionsExport;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleChartInterface;

/**
 * Opens the Topola Genealogy Viewer in embedded mode in an iframe.
 *
 * The Topola Genealogy Viewer is available at
 * https://pewu.github.io/topola-viewer
 */
class TopolaModule extends AbstractModule implements ModuleChartInterface {
    public function getTitle(): string {
        return
            /* I18N: Name of a module */
            I18N::translate('Interactive tree (Topola)');
    }

    public function getDescription(): string {
        return
            /* I18N: Description of the module */
            I18N::translate(
                'Opens the Topola Genealogy Viewer interactive familty tree.');
    }

    public function getChartMenu(Individual $individual) {
		return new Menu(
            $this->getTitle(),
            'module.php?mod=' . $this->getName() .
                '&amp;mod_action=view&amp;rootid=' . $individual->getXref() .
                '&amp;ged=' . $individual->getTree()->getNameUrl(),
			'menu-chart-ancestry',
			array('rel' => 'nofollow')
		);
    }

    public function getBoxChartMenu(Individual $individual) {
        return null;
    }

    /** Exports the database as a GEDCOM file. */
    private function gedcomAction() {
        global $WT_TREE;
        switch (Auth::accessLevel($WT_TREE)) {
            case Auth::PRIV_NONE:
                $privatize = 'gedadmin';
                break;
            case Auth::PRIV_USER:
                $privatize = 'user';
                break;
            default:
                $privatize = 'visitor';
                break;
        }
        $options = array(
            'privatize' => $privatize,
            'toANSI' => false,
            'path' => '',
        );
        $stream = fopen('php://output', 'w');
        FunctionsExport::exportGedcom($WT_TREE, $stream, $options);
    }

    /** Renders an iframe with Topola Genealogy Viewer in embedded mode. */
    private function viewAction() {
        global $WT_TREE;
        global $controller;
        $controller = new ChartController;
        $controller
            ->setPageTitle(
                /* I18N: Page title for interactive tree */
                I18N::translate('Interactive tree (Topola)'))
            ->pageHeader()
            ->addExternalJavascript(
                WT_STATIC_URL . WT_MODULES_DIR . $this->getName() .
                    '/topola.js');
        ?>
        <iframe
            id="topolaFrame"
            style="width: 100%; height: 800px;"
            src="https://pewu.github.io/topola-viewer/#/view?utm_source=webtrees&embedded=true">
        </iframe>
        <?php
    }

    public function modAction($mod_action) {
        switch ($mod_action) {
            case 'gedcom':
                $this->gedcomAction();
                break;
            case 'view':
                $this->viewAction();
                break;
        }
    }
}

return new TopolaModule(__DIR__);
