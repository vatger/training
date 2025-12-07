import { getPositionIcon, getStatusBadge } from '@/pages/endorsements/trainee';
import { Endorsement } from '@/types';
import { AlertCircle, Calendar, Clock } from 'lucide-react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';
import ActivityProgress from './activity-progress';
import { Tooltip, TooltipContent, TooltipTrigger } from '../ui/tooltip';

export default function Tier1EndorsementsTable({ endorsements }: { endorsements: Endorsement[] }) {
    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Position</TableHead>
                        <TableHead>Activity Progress</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Last Activity</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {endorsements.map((endorsement) => (
                        <TableRow key={endorsement.position} className="h-18">
                            <TableCell>
                                <div className="flex items-center gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        {getPositionIcon(endorsement.type)}
                                    </div>
                                    <div>
                                        <div className="font-medium">{endorsement.position}</div>
                                        <div className="text-sm text-muted-foreground">{endorsement.fullName}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <ActivityProgress current={endorsement.activity!} status={endorsement.status} />
                            </TableCell>
                            <TableCell>
                                <div className="flex flex-col gap-1">
                                    {getStatusBadge(endorsement.status)}
                                    {endorsement.removalDate && (
                                        <div className="flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                                            <AlertCircle className="h-3 w-3" />
                                            Removal: {new Date(endorsement.removalDate).toLocaleDateString('de')}
                                        </div>
                                    )}
                                </div>
                            </TableCell>
                            <TableCell>
                                <div className="flex flex-col gap-1">
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Tooltip>
                                            <TooltipTrigger>
                                                <div className="flex gap-2">
                                                    <Calendar className="h-4 w-4" />
                                                    {endorsement.lastActivity && endorsement.lastActivity !== 'Never'
                                                        ? new Date(endorsement.lastActivity).toLocaleDateString('de')
                                                        : 'Never'}
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {endorsement.lastUpdated && (
                                                    <div className="flex items-center gap-1 text-xs">
                                                        <Clock className="h-3 w-3" />
                                                        Activity Last Updated: {new Date(endorsement.lastUpdated).toLocaleDateString('de')}
                                                    </div>
                                                )}
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}