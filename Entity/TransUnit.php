<?php

namespace Lexik\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Lexik\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * @UniqueEntity(fields={"key", "domain", "client", "bundle"})
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnit extends TransUnitModel implements TransUnitInterface
{
    /**
     * Add translations
     *
     * @param Lexik\Bundle\TranslationBundle\Entity\Translation $translations            
     */
    public function addTranslation(\Lexik\Bundle\TranslationBundle\Model\Translation $translation)
    {
        $translation->setTransUnit ( $this );
        
        $this->translations [] = $translation;
    }
    
    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime ( "now" );
        $this->updatedAt = new \DateTime ( "now" );
    }
    
    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime ( "now" );
    }
    
    /*
     * (non-PHPdoc)
     * @see \Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface::getTranslations()
     */
    public function getTranslations()
    {
        return $this->translations;
    }
    
    /*
     * (non-PHPdoc)
     * @see \Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface::hasTranslation()
     */
    public function hasTranslation($locale)
    {
        parent::hasTranslation($locale);
    }
    
    /*
     * (non-PHPdoc)
     * @see \Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface::getTranslation()
     */
    public function getTranslation($locale)
    {
        parent::getTranslation($locale);
    }
    
    /*
     * (non-PHPdoc)
     * @see \Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface::setKey()
     */
    public function setKey($key)
    {
        parent::setKey($key);
    }
    
    /*
     * (non-PHPdoc)
     * @see \Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface::setDomain()
     */
    public function setDomain($domain)
    {
        parent::setDomain($domain);
    }
}
