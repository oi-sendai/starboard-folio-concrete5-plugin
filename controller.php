<?php
namespace Application\Block\StarboardFolio;

use Concrete\Core\Block\BlockController;
use Loader;
use Page;

class Controller extends BlockController
{
    protected $btTable = 'btStarboardFolio';
    protected $btExportTables = array('btStarboardFolio', 'btStarboardFolioEntries');
    protected $btInterfaceWidth = "600";
    protected $btWrapperClass = 'ccm-ui';
    protected $btInterfaceHeight = "465";
    protected $btCacheBlockRecord = true;
    protected $btExportFileColumns = array('fID');
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btIgnorePageThemeGridFrameworkContainer = true;


    public function getBlockTypeDescription()
    {
        return t("A full width slideshow for a photography portfolio.");
    }

    public function getBlockTypeName()
    {
        return t("Starboard Folio");
    }

    public function getSearchableContent()
    {
        $content = '';
        $db = Loader::db();
        $v = array($this->bID);
        $q = 'select * from btStarboardFolioEntries where bID = ?';
        $r = $db->query($q, $v);
        foreach($r as $row) {
           $content.= $row['title'].' ';
           $content.= $row['description'].' ';
        }
        return $content;
    }

    public function add()
    {
        $this->requireAsset('core/file-manager');
        $this->requireAsset('core/sitemap');
        $this->requireAsset('redactor');
    }

    public function edit()
    {
        $this->requireAsset('core/file-manager');
        $this->requireAsset('core/sitemap');
        $this->requireAsset('redactor');
        $db = Loader::db();
        $query = $db->GetAll('SELECT * from btStarboardFolioEntries WHERE bID = ? ORDER BY sortOrder', array($this->bID));
        $this->set('rows', $query);
    }

    public function composer()
    {
        $this->edit();
    }

    // I think this is a singleton
    // public function on_start()
    // {
    //     $al = \Concrete\Core\Asset\AssetList::getInstance();
    //     $al->register(
    //         'javascript', 'jssorslider', 'blocks/starboard_folio/js/jssor.slider.mini.js',
    //         array('version' => '1.0.0', 'minify' => false, 'combine' => true)
    //     );
    //     $al->registerGroup('starboardfolio', array(
    //         // array('javascript', 'jquery'),
    //         // array('javascript', 'jssorslider')
    //     ));
    //     // Finally, we require the asset from within a registerViewAssets() method in our block controller:
    //     // Router::register(
    //     //     '/my/ajax/controller/animal/{favorite}', 
    //     //     'Application\Block\StarboardFolio::getdata'
    //     // );
    // }


    public function registerViewAssets()
    {
        // $this->requireAsset('starboardfolio');
    }

    public function getEntries()
    {
        $db = Loader::db();
        $r = $db->GetAll('SELECT * from btStarboardFolioEntries WHERE bID = ? ORDER BY sortOrder', array($this->bID));
        // in view mode, linkURL takes us to where we need to go whether it's on our site or elsewhere
        $rows = array();
        foreach($r as $q) {
            if (!$q['linkURL'] && $q['internalLinkCID']) {
                $c = Page::getByID($q['internalLinkCID'], 'ACTIVE');
                $q['linkURL'] = $c->getCollectionLink();
                $q['linkPage'] = $c;
            }
            $rows[] = $q;
        }
        return $rows;
    }

    public function view()
    {
        $this->set('rows', $this->getEntries());
    }

    public function duplicate($newBID) {
        parent::duplicate($newBID);
        $db = Loader::db();
        $v = array($this->bID);
        $q = 'select * from btStarboardFolioEntries where bID = ?';
        $r = $db->query($q, $v);
        while ($row = $r->FetchRow()) {
            $db->execute('INSERT INTO btStarboardFolioEntries (bID, fID, linkURL, title, description, sortOrder) values(?,?,?,?,?,?)',
                array(
                    $newBID,
                    $row['fID'],
                    $row['linkURL'],
                    $row['title'],
                    $row['description'],
                    $row['sortOrder']
                )
            );
        }
    }

    public function delete()
    {
        $db = Loader::db();
        $db->delete('btStarboardFolioEntries', array('bID' => $this->bID));
        parent::delete();
    }

    public function save($args)
    {
        $db = Loader::db();
        $db->execute('DELETE from btStarboardFolioEntries WHERE bID = ?', array($this->bID));
        $count = count($args['sortOrder']);
        $i = 0;
        parent::save($args);

        while ($i < $count) {
            $linkURL = $args['linkURL'][$i];
            $internalLinkCID = $args['internalLinkCID'][$i];
            switch (intval($args['linkType'][$i])) {
                case 1:
                    $linkURL = '';
                    break;
                case 2:
                    $internalLinkCID = 0;
                    break;
                default:
                    $linkURL = '';
                    $internalLinkCID = 0;
                    break;
            }

            $db->execute('INSERT INTO btStarboardFolioEntries (bID, fID, title, description, sortOrder, linkURL, internalLinkCID) values(?, ?, ?, ?,?,?,?)',
                array(
                    $this->bID,
                    intval($args['fID'][$i]),
                    $args['title'][$i],
                    $args['description'][$i],
                    $args['sortOrder'][$i],
                    $linkURL,
                    $internalLinkCID
                )
            );
            $i++;
        }
    }



}