<?php

namespace Protalk\MediaBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * MediaRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MediaRepository extends EntityRepository
{
    /**
     * Query database for a fixed number of media records ordered by
     * a specific field
     *
     * @param string $orderField
     * @param int $page
     * @param int $max
     * 
     * @return array Array with total and results
     */
    public function getMediaOrderedBy($orderField, $page, $max)
    {
        $results = $this->getEntityManager()
            ->createQuery('SELECT m
                           FROM ProtalkMediaBundle:Media m
                           WHERE m.isPublished = 1
                           ORDER BY m.'.$orderField.' DESC')
            ->getResult();
        
        return $this->getResultList($results, $page, $max);
    }
    
    /**
     * Create result list by manually doing the limit/offset
     * 
     * @param array $results
     * @param int $page
     * @param int $max
     * 
     * @return array Array with total and results 
     */
    private function getResultList($results, $page, $max)
    {
        $start = ($page - 1) * $max;
        $end = ($page * $max) - 1;
        $total = count($results);
        
        $result = array();
        for ($i = $start; $i <= $end && $i < $total; $i++) {
            $result[] = $results[$i];
        }
        
        return array('total' => $total, 'results' => $result);
    }
    
    /**
     * Find media by search term
     * 
     * @param string $search
     * @param string $sort
     * @param int $page
     * @param int $max 
     * 
     * @return array Array with count and result
     */
    public function findMedia($search, $sort, $page, $max)
    {
        $results = $this->getEntityManager()
                ->createQuery("SELECT DISTINCT m 
                               FROM ProtalkMediaBundle:Media m
                               LEFT JOIN m.categories c
                               LEFT JOIN m.tags t
                               JOIN m.speakers s
                               JOIN m.mediatype mtype
                               WHERE (
                                    LOWER(c.name) LIKE :search1 OR
                                    LOWER(t.name) LIKE :search2 OR
                                    LOWER(s.name) LIKE :search3 OR
                                    LOWER(m.title) LIKE :search4 OR 
                                    LOWER(m.description) LIKE :search5 OR 
                                    LOWER(mtype.name) LIKE :search6
                                   )
                               AND m.isPublished = 1
                               ORDER BY m.".$sort." DESC")
                ->setParameter('search1', '%'.strtolower($search).'%')
                ->setParameter('search2', '%'.strtolower($search).'%')
                ->setParameter('search3', '%'.strtolower($search).'%')
                ->setParameter('search4', '%'.strtolower($search).'%')
                ->setParameter('search5', '%'.strtolower($search).'%')
                ->setParameter('search6', '%'.strtolower($search).'%')
                ->getResult();
        
        return $this->getResultList($results, $page, $max);
    }

    /**
     * Override native findOneBySlug method to include
     * mediatype join, reducing no. of queries to db
     * and increment no of visits made to media item
     *
     * @param string $slug
     * @return Doctrine Record
     */
    public function findOneBySlug($slug)
    {
        return $this->getEntityManager()
                      ->createQuery('SELECT m, mt
                                     FROM ProtalkMediaBundle:Media m
                                     JOIN m.mediatype mt
                                     WHERE m.slug = :slug AND m.isPublished = 1')
                      ->setParameter('slug', $slug)
                      ->getSingleResult();
    }

    /**
     * Find media items by category
     *
     * @param int $categoryId
     * @param string $orderField
     * @param int $page
     * @param int $max
     * 
     * @return array Array with total and results
     */
    public function findByCategory($categoryId, $orderField, $page, $max)
    {
        $results = $this->getEntityManager()
                ->createQuery('SELECT m 
                               FROM ProtalkMediaBundle:Media m
                               JOIN m.categories c
                               WHERE c.id = :catId
                               AND m.isPublished = 1
                               ORDER BY m.'.$orderField.' DESC')
                ->setParameter('catId', $categoryId)               
                ->getResult();
        
        return $this->getResultList($results, $page, $max);
    }

    /**
     * Find media items by tag
     *
     * @param int $tagId
     * @param string $orderField
     * @param int $page
     * @param int $max
     *
     * @return array Array with total and results
     */
    public function findByTag($tagId, $orderField, $page, $max)
    {
        $results = $this->getEntityManager()
                ->createQuery('SELECT m
                               FROM ProtalkMediaBundle:Media m
                               JOIN m.tags t
                               WHERE t.id = :tagId
                               AND m.isPublished = 1
                               ORDER BY m.'.$orderField.' DESC')
                ->setParameter('tagId', $tagId)
                ->getResult();
        
        return $this->getResultList($results, $page, $max);
    }

    /**
     * Find media items by speaker
     *
     * @param int $speakerId
     * @param string $orderField
     * @param int $page
     * @param int $max
     *
     * @return array Array with total and results
     */
    public function findBySpeaker($speakerId, $orderField, $page, $max)
    {
        $results = $this->getEntityManager()
                ->createQuery('SELECT m
                               FROM ProtalkMediaBundle:Media m
                               JOIN m.speakers s
                               WHERE s.id = :speakerId
                               AND m.isPublished = 1
                               ORDER BY m.'.$orderField.' DESC')
                ->setParameter('speakerId', $speakerId)
                ->getResult();
        
        return $this->getResultList($results, $page, $max);
    }

    /**
     * Increment number of visits to media item
     *
     * @param object $media
     */
    public function incrementVisitCount($media)
    {
        $currentVisits = $media->getVisits();
        $media->setVisits($currentVisits + 1);
        $this->getEntityManager()->flush();
    }
}