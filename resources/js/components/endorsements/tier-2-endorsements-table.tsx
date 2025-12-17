import { getPositionIcon, getStatusBadge } from "@/pages/endorsements/trainee";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "../ui/table";
import { Endorsement } from "@/types";
import { ExternalLink } from 'lucide-react';
import { Button } from '../ui/button';

export default function Tier2EndorsementsTable({ endorsements }: { endorsements: Endorsement[]}) {
  const getMoodleUrl = (courseId: number) => {
      return `https://moodle.vatsim-germany.org/course/view.php?id=${courseId}`;
  };

  return (
      <div className="rounded-md border">
          <Table>
              <TableHeader>
                  <TableRow>
                      <TableHead>Position</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead className="text-right">Course</TableHead>
                  </TableRow>
              </TableHeader>
              <TableBody>
                  {endorsements.map((endorsement) => (
                      <TableRow key={endorsement.position} className="h-18">
                          <TableCell>
                              <div className="flex items-center gap-3">
                                  <div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-purple-600">
                                      {getPositionIcon(endorsement.type)}
                                  </div>
                                  <div>
                                      <div className="font-medium">{endorsement.position}</div>
                                      <div className="text-sm text-muted-foreground">{endorsement.fullName}</div>
                                  </div>
                              </div>
                          </TableCell>
                          <TableCell>{getStatusBadge(endorsement.status)}</TableCell>
                          <TableCell className="text-right">
                              {endorsement.moodleCourseId && (
                                  <Button variant="outline" size="sm" asChild>
                                      <a
                                          href={getMoodleUrl(endorsement.moodleCourseId)}
                                          target="_blank"
                                          rel="noopener noreferrer"
                                          className="flex items-center gap-2"
                                      >
                                          View Course
                                          <ExternalLink className="h-3 w-3" />
                                      </a>
                                  </Button>
                              )}
                          </TableCell>
                      </TableRow>
                  ))}
              </TableBody>
          </Table>
      </div>
  );
}