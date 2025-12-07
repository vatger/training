import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-full max-w-8 items-center justify-center rounded-md bg-vatger-blue">
                <AppLogoIcon />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="mb-0.5 truncate text-base leading-tight font-semibold">Training</span>
                <span className="truncate text-xs leading-tight">VATSIM Germany</span>
            </div>
        </>
    );
}
