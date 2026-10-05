<?php

declare(strict_types=1);

namespace Modules\Contacts\Models;

use CodeIgniter\Model;
use Modules\Contacts\Entities\Contact;

class ContactModel extends Model
{
    protected $table          = 'contacts';
    protected $primaryKey     = 'id';
    protected $returnType     = Contact::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'household_id', 'person_id', 'name', 'organization', 'kind', 'phone', 'phone2', 'email', 'address', 'web', 'note',
    ];

    protected $validationRules = [
        'household_id' => 'required|is_natural_no_zero',
        'name'         => 'required|max_length[120]',
        'organization' => 'permit_empty|max_length[120]',
        'kind'         => 'required|in_list[lekar,skola,skolka,kruzok,servis,urad,rodina,priatelia,iny]',
        'phone'        => 'permit_empty|max_length[40]',
        'phone2'       => 'permit_empty|max_length[40]',
        'email'        => 'permit_empty|valid_email|max_length[120]',
        'address'      => 'permit_empty|max_length[255]',
        'web'          => 'permit_empty|max_length[255]',
        'person_id'    => 'permit_empty|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'name'  => ['required' => 'Meno je povinné.'],
        'email' => ['valid_email' => 'E-mail nemá správny tvar.'],
    ];

    /**
     * @return list<Contact>
     */
    public function search(int $householdId, string $query = '', string $kind = ''): array
    {
        $builder = $this->where('household_id', $householdId);
        if ($kind !== '') {
            $builder->where('kind', $kind);
        }
        if ($query !== '') {
            $builder->groupStart()
                ->like('name', $query)
                ->orLike('organization', $query)
                ->orLike('phone', $query)
                ->orLike('email', $query)
                ->orLike('note', $query)
                ->groupEnd();
        }

        return $builder->orderBy('name')->findAll();
    }
}
