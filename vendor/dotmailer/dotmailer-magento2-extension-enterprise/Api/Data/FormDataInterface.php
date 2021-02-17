<?php

namespace Dotdigitalgroup\Enterprise\Api\Data;

interface FormDataInterface
{
    /**
     * @return string
     */
    public function getFormName();

    /**
     * @param string $name
     * @return $this
     */
    public function setFormName($name);

    /**
     * @return string
     */
    public function getFormPageId();

    /**
     * @param $pageId
     * @return $this
     */
    public function setFormPageId($pageId);

    /**
     * @return string
     */
    public function getFormDomain();

    /**
     * @param string $domain
     * @return $this
     */
    public function setFormDomain($domain);

    /**
     * @return string
     */
    public function getScriptSrc();

    /**
     * @param string $src
     * @return $this
     */
    public function setScriptSrc($src);

    /**
     * @return string
     */
    public function getFormSharing();

    /**
     * @param string $sharing
     * @return $this
     */
    public function setFormSharing($sharing);
}
