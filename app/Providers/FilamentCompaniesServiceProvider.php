<?php

namespace App\Providers;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\Company;
use Filament\PanelProvider;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Http\Middleware\Authenticate;
use App\Actions\FilamentCompanies\DeleteUser;
use Wallo\FilamentCompanies\Pages\Auth\Login;
use Wallo\FilamentCompanies\FilamentCompanies;
use Illuminate\Session\Middleware\StartSession;
use Wallo\FilamentCompanies\Pages\User\Profile;
use App\Actions\FilamentCompanies\CreateNewUser;
use App\Actions\FilamentCompanies\DeleteCompany;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Wallo\FilamentCompanies\Pages\Auth\Register;
use App\Actions\FilamentCompanies\UpdateCompanyName;
use App\Actions\FilamentCompanies\AddCompanyEmployee;
use App\Actions\FilamentCompanies\UpdateUserPassword;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Actions\FilamentCompanies\InviteCompanyEmployee;
use App\Actions\FilamentCompanies\RemoveCompanyEmployee;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Wallo\FilamentCompanies\Pages\Company\CreateCompany;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Wallo\FilamentCompanies\Pages\Company\CompanySettings;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Actions\FilamentCompanies\UpdateUserProfileInformation;

class FilamentCompaniesServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('company')
            ->path('company')
            ->default()
            ->login(Login::class)
            ->passwordReset()
            ->homeUrl(static function (): ?string {
                $user = Auth::user();

                if ($company = $user?->primaryCompany()) {
                    return Pages\Dashboard::getUrl(panel: FilamentCompanies::getCompanyPanel(), tenant: $company);
                }

                return Filament::getPanel(FilamentCompanies::getCompanyPanel())->getTenantRegistrationUrl();
            })
            ->plugin(
                FilamentCompanies::make()
                    ->userPanel('admin')
                    ->switchCurrentCompany()
                    ->updateProfileInformation()
                    ->updatePasswords()
                    ->manageBrowserSessions()
                    ->accountDeletion()
                    ->profilePhotos()
                    ->api()
                    ->companies(invitations: true)
                    ->autoAcceptInvitations()
                    ->termsAndPrivacyPolicy()
                    ->notifications()
                    ->modals()
                    ->addProfileComponents([
                        7 => \App\Livewire\ConnectFacebook::class,
                        8 => \App\Livewire\UpdateTimezoneForm::class,
                    ])

            )
            ->registration(Register::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->tenant(Company::class)
            ->tenantProfile(CompanySettings::class)
            ->tenantRegistration(CreateCompany::class)
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profile')
                    ->icon('heroicon-o-user-circle')
                    ->url(static fn() => Profile::getUrl(panel: FilamentCompanies::getUserPanel())),
            ])
            ->authGuard('web')
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->viteTheme('resources/css/filament/company/theme.css')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        FilamentCompanies::createUsersUsing(CreateNewUser::class);
        FilamentCompanies::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        FilamentCompanies::updateUserPasswordsUsing(UpdateUserPassword::class);

        FilamentCompanies::createCompaniesUsing(CreateCompany::class);
        FilamentCompanies::updateCompanyNamesUsing(UpdateCompanyName::class);
        FilamentCompanies::addCompanyEmployeesUsing(AddCompanyEmployee::class);
        FilamentCompanies::inviteCompanyEmployeesUsing(InviteCompanyEmployee::class);
        FilamentCompanies::removeCompanyEmployeesUsing(RemoveCompanyEmployee::class);
        FilamentCompanies::deleteCompaniesUsing(DeleteCompany::class);
        FilamentCompanies::deleteUsersUsing(DeleteUser::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        FilamentCompanies::defaultApiTokenPermissions(['read']);

        FilamentCompanies::role('admin', 'Administrator', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Administrator users can perform any action.');

        FilamentCompanies::role('editor', 'Editor', [
            'read',
            'create',
            'update',
        ])->description('Editor users have the ability to read, create, and update.');
    }
}
