<?php
namespace Omeka\Api\Representation\Entity;

class UserRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            '@id'            => $this->getAdapter()->getApiUrl($entity),
            'o:id'       => $entity->getId(),
            'o:username' => $entity->getUsername(),
            'o:name'     => $entity->getName(),
            'o:email'    => $entity->getEmail(),
            'o:created'  => $this->getDateTime($entity->getCreated()),
            'o:role'     => $entity->getRole(),
        );
    }

    public function getUsername()
    {
        return $this->getData()->getUsername();
    }
}
