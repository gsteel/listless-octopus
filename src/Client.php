<?php

declare(strict_types=1);

namespace ListInterop\Octopus;

use ListInterop\Action\IsSubscribed;
use ListInterop\Action\Subscribe;
use ListInterop\Action\Unsubscribe;
use ListInterop\EmailAddress;
use ListInterop\ListId;
use ListInterop\Octopus\Exception\Exception;
use ListInterop\Octopus\Exception\MailingListNotFound;
use ListInterop\Octopus\Exception\MemberAlreadySubscribed;
use ListInterop\Octopus\Exception\MemberNotFound;
use ListInterop\Octopus\Value\Contact;
use ListInterop\Octopus\Value\ListId as ID;
use ListInterop\Octopus\Value\MailingList;
use ListInterop\Octopus\Value\SubscriptionStatus;
use ListInterop\SubscriberInformation;

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

    /**
     * Change only the subscription status of an existing list member.
     *
     * @throws MemberNotFound if the contact does not exist on the list.
     */
    public function changeSubscriptionStatus(
        EmailAddress $forAddress,
        ListId $onList,
        SubscriptionStatus $toStatus
    ): Contact;

    /**
     * Find a mailing list by its unique identifier or throw an exception.
     *
     * @throws Exception if anything doesn't work.
     * @throws MailingListNotFound if the list does not exist.
     */
    public function findMailingListById(ListId $id): MailingList;

    /**
     * Create a brand spanking new mailing list
     *
     * @throws Exception
     */
    public function createMailingList(string $name): ID;

    /**
     * Delete a mailing list
     *
     * @throws Exception if the operation fails.
     */
    public function deleteMailingList(ListId $listId): void;

    /**
     * Delete a list contact
     *
     * @throws Exception if the operation fails.
     */
    public function deleteListContact(EmailAddress $address, ListId $fromList): void;
}
