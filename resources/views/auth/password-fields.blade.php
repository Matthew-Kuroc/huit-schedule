<div class="field">
    <label for="password">{{ $passwordLabel ?? 'Mật khẩu mới' }}</label>
    <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" maxlength="72" required
           @error('password') aria-invalid="true" aria-describedby="new-password-help password-error" @else aria-describedby="new-password-help" @enderror>
    <p id="new-password-help" class="muted field-hint">Dùng ít nhất 10 ký tự.</p>
    @error('password')<p class="field-error" id="password-error" role="alert">{{ $message }}</p>@enderror
</div>
<div class="field">
    <label for="password_confirmation">Nhập lại {{ mb_strtolower($passwordLabel ?? 'mật khẩu mới') }}</label>
    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" maxlength="72" required>
</div>
