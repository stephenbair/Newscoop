<?php

/**
 * @package Campsite
 */


final class MetaArticleBodyField {
    /**
     * Stores the number of the subtitle that has to be displayed
     *
     * @var int
     */
    private $m_subtitleNumber;


    /**
     * Stores the subtitle objects
     *
     * @var array of MetaSubtitle
     */
    private $m_subtitles;


    /**
     * Stores the subtitles names
     *
     * @var array of string
     */
    private $m_sutitlesNames;


    /**
     * Stores the article object that owns the body field
     *
     * @var MetaArticle
     */
    private $m_parent;


    /**
     * Stores the object describing the article type field
     *
     * @var ArticleTypeField
     */
    private $m_articleTypeField;


    /**
     * Stores the body field name
     */
    private $m_fieldName;


    /**
     * Constructor
     *
     * @param string $p_content
     */
    public function __construct($p_content, MetaArticle $p_parent, $p_fieldName,
                                $p_articleName, $p_subtitleNumber = null,
                                $p_headerFormatStart = null, $p_headerFormatEnd = null) {
        $this->m_subtitleNumber = $p_subtitleNumber;
        $this->m_subtitles = MetaSubtitle::ReadSubtitles($p_content, $p_fieldName, $p_articleName,
                                                         $p_headerFormatStart, $p_headerFormatEnd);
        $this->m_sutitlesNames = array();
        foreach ($this->m_subtitles as $subtitle) {
            $this->m_sutitlesNames = $subtitle->name;
        }
        $this->m_parent = $p_parent;
        $this->m_fieldName = $p_fieldName;
        $this->m_articleTypeField = new ArticleTypeField($p_parent->type_name, $p_fieldName);
    }


    public function __toString() {
        return $this->getContent(!is_null($this->m_subtitleNumber) ? array($this->m_subtitleNumber) : array());
    }


    public function __get($p_property) {
        switch (strtolower($p_property)) {
            case 'all_subtitles': return $this->getContent(array());
            case 'first_paragraph': return $this->getParagraphs($this->__toString(), array(1));
            case 'subtitles_count': return $this->getSubtitlesCount();
            case 'subtitle_number': return $this->m_subtitleNumber;
            case 'subtitle_is_current':
                return $this->m_subtitleNumber == CampTemplate::singleton()->context()->subtitle->number
                && !is_null($this->m_subtitleNumber);
            case 'has_previous_subtitles':
                if (is_null($this->m_subtitleNumber)) {
                    return null;
                }
                return (int)($this->m_subtitleNumber > 0);
            case 'has_next_subtitles':
                if (is_null($this->m_subtitleNumber)) {
                    return null;
                }
                return (int)($this->m_subtitleNumber < ($this->getSubtitlesCount() - 1));
            default:
                $this->trigger_invalid_property_error($p_property);
                return null;
        }
    }


    private function getParagraphs($p_content, array $p_paragraphs = array()) {
        $printAll = count($p_paragraphs) == 0;
        $content = '';
        $paragraphs = preg_split("/(<[\s]*\/?[\s]*p[\s]*>|<[\s]*br[\s]*\/?>([\s]|&nbsp;)*<[\s]*br[\s]*\/?>)/i",
        $p_content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $index = 0;
        $pStart = false;
        foreach ($paragraphs as $paragraph) {
            if (preg_match("/^([\s]|&nbsp;)*$/i", $paragraph)) {
                // This is an empty paragraph, skip it.
                continue;
            }
            if (preg_match("/^<[\s]*\/p[\s]*>$/i", $paragraph)
            || preg_match("/^<[\s]*br[\s]*\/?>([\s]|&nbsp;)*<[\s]*br[\s]*\/?>$/i", $paragraph)) {
                if ($printAll || array_search($index, $p_paragraphs) !== false) {
                    $content .= $paragraph;
                }
                continue;
            } elseif (preg_match("/^<[\s]*p[\s]*>$/i", $paragraph)) {
                // paragraph start
                $pStart = true;
                $index++;
            } else {
                if (!$pStart) {
                    $index++;
                }
                $pStart = false;
            }
            if ($printAll || array_search($index, $p_paragraphs) !== false) {
                $content .= $paragraph;
            }
        }
        return $content;
    }


    /**
     * Returns the content of the given subtitles of the article body field.
     *
     * @param array $p_subtitles
     * @return string
     */
    private function getContent(array $p_subtitles = array())
    {
        $printAll = count($p_subtitles) == 0;
        $content = '';
        foreach ($this->m_subtitles as $index=>$subtitle) {
            if (!$printAll && array_search($index, $p_subtitles) === false) {
                continue;
            }
            $content .= $index > 0 ? $subtitle->formatted_name : '';
            $content .= $subtitle->content;
        }
        if ($this->m_articleTypeField->isContent()) {
            $objectType = new ObjectType('article');
            $userId = CampTemplate::singleton()->context()->user->identifier;
            $requestObjectId = $this->m_parent->request_object_id;
            $updateArticle = empty($requestObjectId);
            try {
                SessionRequest::Create(session_id(), $requestObjectId,
                                       $objectType->getObjectTypeId(), $userId);
                if ($updateArticle) {
                    $article = new Article($this->m_parent->language->number,
                                           $this->m_parent->number);
                    $article->setProperty('object_id', $requestObjectId);
                }
            } catch (Exception $ex) {
                // do something
            }
        }
        return $content;
    }


    /**
     * Returns the array of subtiles existent in the article body field.
     *
     * @return array of MetaSubtitle
     */
    private function getSubtitles() {
        return $this->m_subtitles;
    }


    /**
     * Returns the total number of subtitles
     *
     * @return int
     */
    private function getSubtitlesCount() {
        return count($this->m_subtitles);
    }


    /**
     * Returns an array containing the subtitle names
     *
     * @return array of string
     */
    private function getSubtitlesNames() {
        return $this->m_sutitlesNames;
    }


    protected function trigger_invalid_property_error($p_property, $p_smarty = null)
    {
        $errorMessage = INVALID_PROPERTY_STRING . " $p_property "
                        . OF_OBJECT_STRING . ' article->' . $this->m_fieldName;
        CampTemplate::singleton()->trigger_error($errorMessage, $p_smarty);
    }
}

?>