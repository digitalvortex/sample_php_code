<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Base;

/**
 * Contact Model
 * 
 * Handles contact form submissions and database operations.
 * Follows the Base.php pattern with PHP 8.4 strict typing.
 */
class Contact extends Base
{
    protected static string $table = 'contacts';
    
    /** @var array<string> */
    protected static array $fillable = ['name', 'email', 'message', 'created_at', 'updated_at'];
    
    /** @var array<string> */
    protected static array $encrypted = [];
    
    /** @var array<string> */
    protected static array $hidden = [];
    
    /**
     * Create a new contact submission.
     *
     * @param array<string, mixed> $data Contact data
     * @return static New contact instance
     */
    public static function createContact(array $data): static
    {
        return static::create($data);
    }
    
    /**
     * Retrieve all contact submissions.
     *
     * @return array<static>
     */
    public static function findAllContacts(): array
    {
        return static::all();
    }
    
    /**
     * Retrieve a single contact submission by ID.
     *
     * @param int $id
     * @return static|null
     */
    public static function findContactById(int $id): ?static
    {
        return static::find($id);
    }
    
    /**
     * Update an existing contact submission.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public static function updateContact(int $id, array $data): bool
    {
        $contact = static::find($id);
        return $contact ? $contact->update($data) : false;
    }
    
    /**
     * Delete a contact submission.
     *
     * @param int $id
     * @return bool
     */
    public static function deleteContact(int $id): bool
    {
        $contact = static::find($id);
        return $contact ? $contact->delete() : false;
    }
    
    /**
     * Get the contact's name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name') ?? '';
    }
    
    /**
     * Get the contact's email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getAttribute('email') ?? '';
    }
    
    /**
     * Get the contact's message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->getAttribute('message') ?? '';
    }
}