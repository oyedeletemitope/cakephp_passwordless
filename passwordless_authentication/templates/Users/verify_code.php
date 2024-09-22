<!-- In src/Template/Users/verify_code.php -->

<h1>Verify Your Code</h1>

<?php
$verificationData = $this->request->getSession()->read('VerificationData');
if ($verificationData):
?>
    <p>A verification code has been sent to <?= h($verificationData['phone_number']) ?>.</p>
    <p>Please enter the code below:</p>
<?php endif; ?>

<?= $this->Form->create() ?>
<fieldset>
    <legend><?= __('Enter Verification Code') ?></legend>
    <?= $this->Form->control('verification_code', ['required' => true]) ?>
</fieldset>
<?= $this->Form->button(__('Verify')) ?>
<?= $this->Form->end() ?>