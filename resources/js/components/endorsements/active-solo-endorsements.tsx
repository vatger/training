import { Endorsement } from "@/types";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "../ui/card";
import { CheckCircle, Clock } from "lucide-react";
import { getPositionIcon } from "@/pages/endorsements/trainee";

export default function ActiveSoloEndorsements({ endorsements }: { endorsements: Endorsement[] }) {
    const activeSolos = endorsements.filter((e) => e.status === 'active');

    if (activeSolos.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <div className="rounded-full bg-primary/10 p-2 dark:bg-primary/20">
                        <CheckCircle className="h-5 w-5 text-primary dark:text-primary" />
                    </div>
                    Active Solo Endorsements
                </CardTitle>
                <CardDescription>
                    You have {activeSolos.length} active solo endorsement{activeSolos.length > 1 ? 's' : ''} from your mentor
                    {activeSolos.length > 1 ? 's' : ''}.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="grid gap-3">
                    {activeSolos.map((endorsement) => (
                        <div key={endorsement.position} className="flex items-center justify-between rounded-lg border p-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-blue-900 dark:text-blue-400">
                                    {getPositionIcon(endorsement.type)}
                                </div>
                                <div>
                                    <div className="font-medium">{endorsement.position}</div>
                                    <div className="text-sm text-muted-foreground">{endorsement.fullName}</div>
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="text-sm font-medium">{endorsement.mentor}</div>
                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock className="h-3 w-3" />
                                    Expires {new Date(endorsement.expiresAt!).toLocaleDateString('de')}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}