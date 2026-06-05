<?php
namespace Requests\PurchaseOrder;

use Requests;

class ChangeStatusRequest extends Requests {
    public function rules(): void {
        $comment = $this->input('comentario');
        if (empty($comment) || strlen(trim($comment)) < 5) {
            $this->addError('comentario', 'El comentario es obligatorio y debe tener al menos 5 caracteres.');
        }
    }
}
?>