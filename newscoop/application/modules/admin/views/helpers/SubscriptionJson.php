<?php

/**
 * Subscription Json
 */
class Admin_View_Helper_SubscriptionJson extends Zend_View_Helper_Abstract
{
    /**
     * Get json representation of subscription
     *
     * @param Newscoop\Entity\Subscription $subscription
     * @return array
     */
    public function SubscriptionJson(\Newscoop\Subscription\Subscription $subscription)
    {
        return array(
            'id' => $subscription->getId(),
            'publication' => array(
                'id' => $subscription->getPublicationId(),
                'name' => $subscription->getPublicationName(),
            ),
            'toPay' => $subscription->getToPay(),
            'type' => $subscription->getType(),
            'active' => $subscription->isActive(),
        );
    }
}
