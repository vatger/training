import { Progress } from '../ui/progress';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '../ui/tooltip';

const MIN_ACTIVITY_MINUTES = 180;

export default function ActivityProgress({ current, status }: { current: number; status: string }) {
    const formattedCurrent = formatMinutesToHHMM(current) + 'h';
    const formattedMax = '3h';

    // Format for tooltip (Xh Ym)
    const tooltipCurrent = formatMinutesToText(current);
    const tooltipMax = formatMinutesToText(MIN_ACTIVITY_MINUTES);

    const percentage = Math.min((current / MIN_ACTIVITY_MINUTES) * 100, 100);

    let progressColor = 'bg-green-500';
    if (status === 'warning') progressColor = 'bg-yellow-500';
    if (status === 'removal') progressColor = 'bg-red-500';

    return (
        <TooltipProvider>
            <Tooltip>
                <TooltipTrigger asChild>
                    <div className="max-w-40">
                        <div className="mb-1 flex justify-between text-xs">
                            <span>{formattedCurrent}</span>
                            <span>of</span>
                            <span>{formattedMax}</span>
                        </div>

                        <Progress value={percentage} className={`h-2`} colorClass={progressColor} />
                    </div>
                </TooltipTrigger>

                <TooltipContent>
                    <p className="text-primary-foreground">
                        {tooltipCurrent} of minimum {tooltipMax} in the last 180 days ({percentage.toFixed(1)}%)
                    </p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}

function formatMinutesToHHMM(totalMinutes: number) {
    const minutes = Math.round(totalMinutes);
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

function formatMinutesToText(totalMinutes: number) {
    const minutes = Math.round(totalMinutes);
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hrs > 0 && mins > 0) return `${hrs}h ${mins}m`;
    if (hrs > 0) return `${hrs}h`;
    return `${mins}m`;
}
