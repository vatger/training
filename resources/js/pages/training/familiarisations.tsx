import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

type Familiarisation = {
  cid: number;
  stations: string[];
};

export default function Familiarisations({
  familiarisations,
}: {
  familiarisations: Familiarisation[];
}) {
  return (
    <div className="space-y-3 m-4">
      {familiarisations.map((familiarisation) => (
        <Card key={familiarisation.cid}>
          <CardContent className="flex items-center gap-4">
            <div className="w-24 shrink-0 font-mono font-semibold">
              {familiarisation.cid}
            </div>

            <div className="flex flex-wrap gap-2">
              {familiarisation.stations.map((station) => (
                <Badge key={station} variant="secondary">
                  {station}
                </Badge>
              ))}
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}