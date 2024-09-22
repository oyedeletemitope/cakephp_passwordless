<!-- In src/Template/Users/request_code.php -->

<h1>Request Verification Code</h1>

<?= $this->Form->create() ?>
<fieldset>
    <legend><?= __('Enter Your Phone Number') ?></legend>
    <?= $this->Form->control('phone_number', ['required' => true]) ?>
</fieldset>
<?= $this->Form->button(__('Request Code')) ?>
<?= $this->Form->end() ?>