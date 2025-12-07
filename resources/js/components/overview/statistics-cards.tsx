import { Badge } from '@/components/ui/badge';
import { Card, CardAction, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { MentorStatistics } from '@/types/mentor';
import { TrendingUp } from 'lucide-react';

interface StatisticsCardsProps {
    statistics: MentorStatistics;
}

export function StatisticsCards({ statistics }: StatisticsCardsProps) {
    return (
        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
            <Card className="@container/card">
                <CardHeader>
                    <CardDescription>Active Trainees</CardDescription>
                    <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                        {statistics.activeTrainees}
                    </CardTitle>
                </CardHeader>
                <CardFooter className="text-sm">
                    <div className="text-muted-foreground">Active trainees across all of your courses</div>
                </CardFooter>
            </Card>

            <Card className="@container/card">
                <CardHeader>
                    <CardDescription>Claimed Trainees</CardDescription>
                    <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                        {statistics.claimedTrainees}
                    </CardTitle>
                </CardHeader>
                <CardFooter className="flex-col items-start gap-1.5 text-sm">
                    <div className="text-muted-foreground">Trainees in courses you mentor</div>
                </CardFooter>
            </Card>

            <Card className="@container/card">
                <CardHeader>
                    <CardDescription>Training Sessions</CardDescription>
                    <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                        {statistics.trainingSessions}
                    </CardTitle>
                    {statistics.trainingSessions > 0 && (
                        <CardAction>
                            <Badge>
                                <TrendingUp />
                                +12.5%
                            </Badge>
                        </CardAction>
                    )}
                </CardHeader>
                <CardFooter className="text-sm text-muted-foreground">Training sessions held the last 30 days</CardFooter>
            </Card>
        </div>
    );
}