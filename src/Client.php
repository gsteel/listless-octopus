<?php

declare(strict_types=1);

namespace GSteel\Listless\Octopus;

use GSteel\Listless\Action\IsSubscribed;
use GSteel\Listless\Action\Subscribe;
use GSteel\Listless\Action\Unsubscribe;
use GSteel\Listless\EmailAddress;
use GSteel\Listless\ListId;
use GSteel\Listless\Octopus\Exception\MemberAlreadySubscribed;
use GSteel\Listless\Octopus\Exception\MemberNotFound;
use GSteel\Listless\Octopus\Value\Contact;
use GSteel\Listless\Octopus\Value\SubscriptionStatus;
use GSteel\Listless\SubscriberInformation;

interface Client extends Subscribe, IsSubscribed, Unsubscribe
{
    /**
     * Return a hash of an email address specifically for use with Email Octopus' declared requirements
     */
    public function emailAddressHash(EmailAddress $address): string;

    /**
     * Add a contact to a list that does not yet exist
     *
     * @throws MemberAlreadySubscribed if the email address exists on the list in any state.
     */
    public function addContactToList(
        EmailAddress $address,
        ListId $listId,
        ?SubscriberInformation $fields = null,
        ?SubscriptionStatus $status = null
    ): Contact;

    /**
     * Find an existing contact present on the given list
     *
     * @throws MemberNotFound if the contact does not exist on the list.
     */
    public function findListContactByEmailAddress(EmailAddress $address, ListId $listId): Contact;
}
