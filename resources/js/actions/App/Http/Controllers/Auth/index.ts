import AdminAuthController from './AdminAuthController'
import AuthenticatedSessionController from './AuthenticatedSessionController'
import VatsimOAuthController from './VatsimOAuthController'

const Auth = {
    AdminAuthController: Object.assign(AdminAuthController, AdminAuthController),
    AuthenticatedSessionController: Object.assign(AuthenticatedSessionController, AuthenticatedSessionController),
    VatsimOAuthController: Object.assign(VatsimOAuthController, VatsimOAuthController),
}

export default Auth